<?php

use App\Jobs\FetchOgImage;
use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use App\Models\User;
use App\Services\ProductLogoStorageService;
use App\Support\CategoryTypeRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

it('optimizes remotely selected logos before saving a submitted product', function () {
    Storage::fake('public');
    Queue::fake();
    Role::create(['name' => 'admin']);

    $user = User::factory()->create();
    [$softwareCategory, $pricingCategory, $useCaseCategory] = createSubmissionCategories();

    $sourceLogo = UploadedFile::fake()->image('remote-logo.png', 512, 512);
    $sourceLogoBytes = file_get_contents($sourceLogo->getRealPath());

    Http::fake([
        'https://8.8.8.8/logo.png' => Http::response($sourceLogoBytes, 200, [
            'Content-Type' => 'image/png',
        ]),
    ]);

    $response = $this->actingAs($user)->postJson(route('products.store'), [
        'name' => 'Linear',
        'tagline' => 'Collaborative product design workspace',
        'product_page_tagline' => 'Track issues, projects, and product decisions in one place.',
        'description' => '<p>Built for modern product teams.</p>',
        'link' => 'https://linear.app',
        'categories' => [
            $softwareCategory->id,
            $pricingCategory->id,
            $useCaseCategory->id,
        ],
        'logo_url' => 'https://8.8.8.8/logo.png',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $product = Product::firstOrFail();

    expect($product->logo)->toStartWith('logos/')
        ->toEndWith('.webp');

    Storage::disk('public')->assertExists($product->logo);

    [$width, $height] = getimagesize(Storage::disk('public')->path($product->logo));

    expect($width)->toBe(ProductLogoStorageService::TARGET_WIDTH)
        ->and($height)->toBe(ProductLogoStorageService::TARGET_HEIGHT);

    Queue::assertPushed(FetchOgImage::class);
});

function createSubmissionCategories(): array
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
