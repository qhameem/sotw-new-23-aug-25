<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomCategorySubmission;
use App\Models\Product;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovedProductPendingEditsTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_an_approved_product_stores_a_pending_screenshot_and_custom_use_case(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        [$softwareCategory, $pricingCategory] = $this->createRequiredCategories();

        $product = Product::factory()->create([
            'user_id' => $owner->id,
            'approved' => true,
            'is_published' => true,
        ]);
        $product->categories()->sync([$softwareCategory->id, $pricingCategory->id]);

        $response = $this->actingAs($owner)->put(route('products.update', $product), [
            'tagline' => 'Updated tagline',
            'product_page_tagline' => 'Updated product page tagline',
            'description' => 'Updated description',
            'categories' => [$softwareCategory->id, $pricingCategory->id],
            'custom_categories' => [
                ['name' => 'Domain Management', 'type' => 'use_case'],
            ],
            'media' => [
                UploadedFile::fake()->image('updated-screenshot.png', 1200, 630),
            ],
        ]);

        $response->assertRedirect(route('products.my'));

        $product->refresh();

        $this->assertTrue($product->has_pending_edits);
        $this->assertNotNull($product->proposed_screenshot_path);
        Storage::disk('public')->assertExists($product->proposed_screenshot_path);

        $this->assertDatabaseHas('custom_category_submissions', [
            'product_id' => $product->id,
            'type' => 'use_case',
            'name' => 'Domain Management',
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('product_media', 0);
    }

    public function test_admin_can_approve_pending_edit_screenshot_and_custom_use_case_together(): void
    {
        Storage::fake('public');

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $owner = User::factory()->create();
        [$softwareCategory, $pricingCategory] = $this->createRequiredCategories();
        Type::firstOrCreate(['name' => 'Use Case']);

        $product = Product::factory()->create([
            'user_id' => $owner->id,
            'approved' => true,
            'is_published' => true,
        ]);
        $product->categories()->sync([$softwareCategory->id, $pricingCategory->id]);
        $product->proposedCategories()->sync([$softwareCategory->id, $pricingCategory->id]);
        $product->update([
            'has_pending_edits' => true,
            'proposed_tagline' => 'Updated tagline',
            'proposed_product_page_tagline' => 'Updated page tagline',
            'proposed_description' => 'Updated description',
            'proposed_screenshot_path' => 'product_media/proposed-screenshot.png',
            'proposed_screenshot_thumb_path' => 'product_media/thumb_proposed-screenshot.png',
            'proposed_screenshot_medium_path' => 'product_media/medium_proposed-screenshot.png',
        ]);

        Storage::disk('public')->put('product_media/proposed-screenshot.png', 'image');
        Storage::disk('public')->put('product_media/thumb_proposed-screenshot.png', 'thumb');
        Storage::disk('public')->put('product_media/medium_proposed-screenshot.png', 'medium');

        $submission = CustomCategorySubmission::create([
            'product_id' => $product->id,
            'type' => 'use_case',
            'name' => 'Domain Management',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.products.approve-edits', $product), [
            'custom_category_' . $submission->id => 'approve',
            'custom_category_' . $submission->id . '_slug' => 'domain-management',
            'custom_category_' . $submission->id . '_description' => 'Domain management software helps teams handle domains and DNS from one place.',
            'custom_category_' . $submission->id . '_meta_description' => 'Browse domain management software for handling domains, DNS, and registrar workflows.',
        ]);

        $response->assertRedirect(route('admin.products.pending-edits.index'));

        $product->refresh();

        $this->assertFalse($product->has_pending_edits);
        $this->assertNull($product->proposed_screenshot_path);

        $this->assertDatabaseHas('product_media', [
            'product_id' => $product->id,
            'path' => 'product_media/proposed-screenshot.png',
            'type' => 'screenshot',
        ]);
        $this->assertDatabaseHas('categories', [
            'name' => 'Domain Management',
            'slug' => 'domain-management',
        ]);
        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => Category::where('slug', 'domain-management')->value('id'),
        ]);
        $this->assertDatabaseHas('custom_category_submissions', [
            'id' => $submission->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_editing_an_approved_product_creates_pending_edits_instead_of_updating_live(): void
    {
        Storage::fake('public');

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $owner = User::factory()->create();
        [$softwareCategory, $pricingCategory] = $this->createRequiredCategories();

        $product = Product::factory()->create([
            'user_id' => $owner->id,
            'approved' => true,
            'is_published' => true,
            'tagline' => 'Live tagline',
        ]);
        $product->categories()->sync([$softwareCategory->id, $pricingCategory->id]);

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'slug' => $product->slug,
            'link' => $product->link,
            'tagline' => 'Pending admin tagline',
            'product_page_tagline' => 'Pending admin page tagline',
            'description' => 'Pending admin description',
            'categories' => [$softwareCategory->id, $pricingCategory->id],
            'custom_categories' => [
                ['name' => 'Domain Management', 'type' => 'use_case'],
            ],
            'media' => [
                UploadedFile::fake()->image('admin-screenshot.png', 1200, 630),
            ],
        ]);

        $response->assertRedirect(route('admin.products.pending-edits.index'));

        $product->refresh();

        $this->assertSame('Live tagline', $product->tagline);
        $this->assertSame('Pending admin tagline', $product->proposed_tagline);
        $this->assertTrue($product->has_pending_edits);
        $this->assertNotNull($product->proposed_screenshot_path);

        $this->assertDatabaseHas('custom_category_submissions', [
            'product_id' => $product->id,
            'type' => 'use_case',
            'name' => 'Domain Management',
            'status' => 'pending',
        ]);
    }

    public function test_admin_editing_with_local_storage_logo_and_screenshot_previews_persists_pending_media(): void
    {
        Storage::fake('public');

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $owner = User::factory()->create();
        [$softwareCategory, $pricingCategory] = $this->createRequiredCategories();

        $logoFile = UploadedFile::fake()->image('logo-source.png', 100, 100);
        $screenshotFile = UploadedFile::fake()->image('screenshot-source.png', 1200, 630);
        Storage::disk('public')->put('tmp/logo-source.png', file_get_contents($logoFile->getRealPath()));
        Storage::disk('public')->put('tmp/screenshot-source.png', file_get_contents($screenshotFile->getRealPath()));

        $product = Product::factory()->create([
            'user_id' => $owner->id,
            'approved' => true,
            'is_published' => true,
        ]);
        $product->categories()->sync([$softwareCategory->id, $pricingCategory->id]);

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'slug' => $product->slug,
            'link' => $product->link,
            'tagline' => 'Pending admin tagline',
            'product_page_tagline' => 'Pending admin page tagline',
            'description' => 'Pending admin description',
            'categories' => [$softwareCategory->id, $pricingCategory->id],
            'logo_url' => '/storage/tmp/logo-source.png',
            'media_urls' => ['/storage/tmp/screenshot-source.png'],
        ]);

        $response->assertRedirect(route('admin.products.pending-edits.index'));

        $product->refresh();

        $this->assertNotNull($product->proposed_logo_path);
        $this->assertNotNull($product->proposed_screenshot_path);
        Storage::disk('public')->assertExists($product->proposed_logo_path);
        Storage::disk('public')->assertExists($product->proposed_screenshot_path);
    }

    private function createRequiredCategories(): array
    {
        $softwareType = Type::firstOrCreate(['name' => 'Category']);
        $pricingType = Type::firstOrCreate(['name' => 'Pricing']);

        $softwareCategory = Category::factory()->create(['name' => 'Productivity']);
        $pricingCategory = Category::factory()->create(['name' => 'Free']);

        $softwareCategory->types()->syncWithoutDetaching([$softwareType->id]);
        $pricingCategory->types()->syncWithoutDetaching([$pricingType->id]);

        return [$softwareCategory, $pricingCategory];
    }
}
