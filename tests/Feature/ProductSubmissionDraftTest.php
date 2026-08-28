<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSubmissionDraft;
use App\Models\Type;
use App\Models\User;
use App\Support\CategoryTypeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductSubmissionDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_autosave_an_unfinished_product_submission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('product-submission-drafts.store'), [
            'link' => 'https://example.com/product?utm_source=test',
            'name' => 'Example Draft',
            'tagline' => 'Draft tagline',
            'description' => '<p>Draft description.</p>',
            'categories' => [1, 2],
            'useCases' => [3],
            'logoPreview' => 'https://cdn.example.com/logo.png',
        ]);

        $response->assertOk()
            ->assertJsonPath('draft.title', 'Example Draft');

        $draft = ProductSubmissionDraft::firstOrFail();

        $this->assertSame($user->id, $draft->user_id);
        $this->assertSame('https://example.com/product', $draft->link);
        $this->assertSame('Example Draft', $draft->name);
        $this->assertSame('Draft tagline', $draft->payload['tagline']);
    }

    public function test_add_product_page_preloads_selected_unfinished_submission(): void
    {
        $user = User::factory()->create();

        $draft = ProductSubmissionDraft::create([
            'user_id' => $user->id,
            'name' => 'Resume Draft',
            'link' => 'https://resume.example.com',
            'payload' => [
                'name' => 'Resume Draft',
                'link' => 'https://resume.example.com',
                'tagline' => 'Resume tagline',
                'tagline_detailed' => 'Resume detailed tagline',
                'description' => '<p>Resume description.</p>',
                'categories' => [11],
                'useCases' => [22],
                'pricing' => [33],
                'tech_stack' => [44],
                'logoPreview' => 'https://cdn.example.com/resume-logo.png',
            ],
            'last_autosaved_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('products.create', ['draft' => $draft->uuid]));

        $response->assertOk();
        $response->assertSee('data-active-draft-id="' . $draft->uuid . '"', false);
        $response->assertSee('Resume Draft');
        $response->assertSee('resume.example.com');
    }

    public function test_admin_can_view_and_open_unfinished_submissions_from_all_users(): void
    {
        Role::firstOrCreate(['name' => 'admin']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $owner = User::factory()->create([
            'name' => 'Draft Owner',
            'email' => 'draft-owner@example.com',
        ]);

        $draft = ProductSubmissionDraft::create([
            'user_id' => $owner->id,
            'name' => 'Other User Draft',
            'link' => 'https://other-user.example.com',
            'payload' => [
                'name' => 'Other User Draft',
                'link' => 'https://other-user.example.com',
            ],
            'last_autosaved_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('products.create'));

        $response->assertOk();
        $response->assertSee('Other User Draft');
        $response->assertSee('Draft Owner');
        $response->assertSee('draft-owner@example.com');
        $this->assertContains(
            route('products.create', ['draft' => $draft->uuid]),
            collect($response->viewData('submissionDrafts'))->pluck('resume_url')->all()
        );

        $this->actingAs($admin)
            ->get(route('products.create', ['draft' => $draft->uuid]))
            ->assertOk()
            ->assertSee('data-active-draft-id="' . $draft->uuid . '"', false);
    }

    public function test_non_admin_cannot_view_or_open_another_users_unfinished_submission(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $draft = ProductSubmissionDraft::create([
            'user_id' => $owner->id,
            'name' => 'Private Draft',
            'payload' => ['name' => 'Private Draft'],
            'last_autosaved_at' => now(),
        ]);

        $this->actingAs($otherUser)
            ->get(route('products.create'))
            ->assertOk()
            ->assertDontSee('Private Draft');

        $this->actingAs($otherUser)
            ->get(route('products.create', ['draft' => $draft->uuid]))
            ->assertNotFound();
    }

    public function test_submitting_a_product_clears_the_matching_unfinished_submission(): void
    {
        Notification::fake();
        Queue::fake();
        Role::firstOrCreate(['name' => 'admin']);

        $user = User::factory()->create();
        [$softwareCategory, $pricingCategory, $useCaseCategory] = $this->createSubmissionCategories();

        $draft = ProductSubmissionDraft::create([
            'user_id' => $user->id,
            'name' => 'Launch Draft',
            'link' => 'https://launch.example.com',
            'payload' => [
                'name' => 'Launch Draft',
                'link' => 'https://launch.example.com',
            ],
            'last_autosaved_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('products.store'), [
            'name' => 'Launch Draft',
            'tagline' => 'Launch tagline',
            'description' => '<p>Launch description.</p>',
            'link' => 'https://launch.example.com',
            'categories' => [
                $softwareCategory->id,
                $pricingCategory->id,
                $useCaseCategory->id,
            ],
            'submission_type' => 'free',
            'draft_uuid' => $draft->uuid,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Launch Draft',
            'link' => 'https://launch.example.com',
        ]);
        $this->assertDatabaseMissing('product_submission_drafts', [
            'id' => $draft->id,
        ]);
    }

    private function createSubmissionCategories(): array
    {
        $softwareType = Type::create([
            'name' => CategoryTypeRegistry::primaryNameFor(CategoryTypeRegistry::SOFTWARE),
        ]);
        $pricingType = Type::create([
            'name' => CategoryTypeRegistry::primaryNameFor(CategoryTypeRegistry::PRICING),
        ]);
        $useCaseType = Type::create([
            'name' => CategoryTypeRegistry::primaryNameFor(CategoryTypeRegistry::USE_CASE),
        ]);

        $softwareCategory = Category::factory()->create();
        $pricingCategory = Category::factory()->create();
        $useCaseCategory = Category::factory()->create();

        $softwareCategory->types()->attach($softwareType);
        $pricingCategory->types()->attach($pricingType);
        $useCaseCategory->types()->attach($useCaseType);

        return [$softwareCategory, $pricingCategory, $useCaseCategory];
    }
}
