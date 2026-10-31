<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductEditCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_categories_on_product_edit_page()
    {
        // Create admin role and user
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        // Create category types
        $categoryType = Type::create(['name' => 'Category']);
        $bestForType = Type::create(['name' => 'Best for']);
        $pricingType = Type::create(['name' => 'Pricing']);

        // Create categories
        $regularCategory = Category::factory()->create(['name' => 'Regular Category']);
        $bestForCategory = Category::factory()->create(['name' => 'Best For Category']);
        $pricingCategory = Category::factory()->create(['name' => 'Pricing Category']);

        // Attach types to categories
        $regularCategory->types()->attach($categoryType);
        $bestForCategory->types()->attach($bestForType);
        $pricingCategory->types()->attach($pricingType);

        // Create product and attach categories
        $product = Product::factory()->create();
        $product->categories()->attach([$regularCategory->id, $bestForCategory->id]);

        // Mock the category_types.json file
        Storage::fake('local');
        $categoryTypesJson = json_encode([
            ['type_id' => $categoryType->id, 'type_name' => 'Category'],
            ['type_id' => $bestForType->id, 'type_name' => 'Best for'],
            ['type_id' => $pricingType->id, 'type_name' => 'Pricing'],
        ]);
        Storage::disk('local')->put('category_types.json', $categoryTypesJson);

        // Act as admin and visit the edit page
        $response = $this->actingAs($admin)->get(route('admin.products.edit', $product));

        // Assertions
        $response->assertStatus(200);
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $form = $document->getElementById('product-submit-app');
        $this->assertNotNull($form);
        foreach ([
            'data-regular-categories' => $regularCategory,
            'data-best-for-categories' => $bestForCategory,
            'data-pricing-categories' => $pricingCategory,
        ] as $attribute => $category) {
            $categories = collect(json_decode($form->getAttribute($attribute), true));
            $this->assertTrue($categories->contains(fn ($item) => $item['id'] === $category->id && $item['name'] === $category->name));
        }
    }
}
