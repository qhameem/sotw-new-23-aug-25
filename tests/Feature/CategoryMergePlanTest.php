<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use App\Services\CategoryNavigationService;
use App\Services\ProductFilterNavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

it('applies and reverses the category merge plan while preserving taxonomy URLs', function () {
    Log::spy();
    $csv = new SplFileObject(database_path('migrations/data/category-merge-plan-50.csv'));
    $types = collect(['Software', 'Software Categories', 'Use Case', 'Platform'])
        ->mapWithKeys(fn (string $name) => [$name => Type::firstOrCreate(['name' => $name])]);
    $originalTypes = [];
    $samples = collect();
    $csv->fgetcsv(escape: '');

    while (($fields = $csv->fgetcsv(escape: '')) && $fields !== [null]) {
        [$name, $slug, $typeNames, $action] = $fields;
        $category = Category::create(compact('name', 'slug'));
        $ids = collect(explode(', ', $typeNames))->map(fn ($type) => $types[$type]->id)->sort()->values()->all();
        $category->types()->attach($ids);
        $originalTypes[$category->id] = $ids;

        if ($action === 'RETYPE to Platform' || ($action === 'MERGE' && $samples->where('action', 'MERGE')->count() < 5)) {
            $samples->push(['category' => $category, 'url' => $category->publicUrl(), 'action' => $action]);
        }
    }

    $outsidePlan = Category::factory()->create();
    $outsidePlan->types()->attach($types['Software Categories']);
    $originalTypes[$outsidePlan->id] = [$types['Software Categories']->id];
    $outsidePricing = Category::create(['slug' => 'outside-pricing', 'name' => 'Outside Pricing']);
    $outsidePricing->types()->attach(Type::create(['name' => 'Pricing']));

    $sitemapCategoryUrls = function () {
        $originalPublicPath = public_path();
        $temporaryPublicPath = sys_get_temp_dir().'/category-plan-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($temporaryPublicPath);
        app()->usePublicPath($temporaryPublicPath);

        try {
            $this->artisan('sitemap:generate', ['--with-pseo' => true])->assertExitCode(0);

            return collect(['categories', 'use-cases', 'best-for', 'platforms', 'pseo'])
                ->flatMap(function ($name) {
                    $path = public_path("sitemaps/{$name}.xml");
                    if (! File::exists($path)) {
                        return [];
                    }

                    $xml = simplexml_load_file($path);
                    $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

                    return array_map('strval', $xml->xpath('/s:urlset/s:url/s:loc'));
                })->sort()->values()->all();
        } finally {
            app()->usePublicPath($originalPublicPath);
            File::deleteDirectory($temporaryPublicPath);
        }
    };
    $published = Product::factory()->count(3)->create(['approved' => true, 'is_published' => true]);
    foreach ($samples as $sample) {
        $sample['category']->products()->attach($published->modelKeys());
    }
    $before = $samples->map(fn ($sample) => $this->get($sample['url'])->assertOk()->viewData('meta_title'));
    $originalUrls = $sitemapCategoryUrls();
    expect($originalUrls)->toHaveCount($samples->count() * 2);

    $migration = require database_path('migrations/2026_11_21_000002_apply_category_merge_plan.php');
    $migration->up();

    $csvCategories = Category::whereNotIn('id', [$outsidePlan->id, $outsidePricing->id]);
    expect((clone $csvCategories)->submittable()->count())->toBe(55)
        ->and((clone $csvCategories)->where('submittable', false)->count())->toBe(206)
        ->and((clone $csvCategories)->whereNotNull('canonical_slug')->count())->toBe(0)
        ->and($types['Software Categories']->assignedCategories()->count())->toBe(0)
        ->and($types['Software']->categories()->where('categories.id', '!=', $outsidePlan->id)->count())->toBe(256)
        ->and($types['Platform']->categories()->count())->toBe(5);

    $aliases = Type::with('categories')->whereIn('id', [
        $types['Software']->id, $types['Software Categories']->id,
    ])->get()->keyBy('name');
    expect($aliases['Software Categories']->categories->modelKeys())
        ->toBe($aliases['Software']->categories->modelKeys());

    $this->getJson('/api/categories')->assertOk()
        ->assertJsonPath('curated', true)
        ->assertJsonCount(50, 'categories')
        ->assertJsonCount(5, 'platforms')
        ->assertJsonCount(0, 'useCases')
        ->assertJsonCount(0, 'bestFor')
        ->assertJsonCount(1, 'pricing')
        ->assertJsonPath('pricing.0.id', $outsidePricing->id)
        ->assertJsonFragment(['name' => 'AI Agents & Automation'])
        ->assertJsonMissing(['name' => 'AI Agent']);
    $this->get(route('products.create'))->assertOk()
        ->assertViewHas('regularCategories', fn ($options) => $options->count() === 50)
        ->assertViewHas('platformCategories', fn ($options) => $options->count() === 5)
        ->assertViewHas('pricingCategories', fn ($options) => $options->pluck('id')->all() === [$outsidePricing->id])
        ->assertViewHas('useCaseCategories', fn ($options) => $options->isEmpty())
        ->assertViewHas('bestForCategories', fn ($options) => $options->isEmpty());

    $selectionRule = Category::submissionOptionRule(curated: true);
    expect(validator(['category' => $outsidePricing->id], ['category' => $selectionRule])->passes())->toBeTrue()
        ->and(validator(['category' => $outsidePlan->id], ['category' => $selectionRule])->passes())->toBeFalse();

    Cache::forget(CategoryNavigationService::CACHE_KEY);
    app()->forgetInstance(CategoryNavigationService::class);
    $groups = app(CategoryNavigationService::class)->getMenuGroups();
    expect(collect($groups)->firstWhere('key', 'view-all')['item_count'])->toBe(57)
        ->and(app(ProductFilterNavigationService::class)->getTypes()->pluck('name')->all())
        ->not->toContain('Software Categories');

    foreach ($samples as $index => $sample) {
        expect($sample['category']->fresh()->publicUrl())->toBe($sample['url']);
        $this->get($sample['url'])->assertOk()->assertViewHas('meta_title', $before[$index]);
    }
    expect($sitemapCategoryUrls())->toBe($originalUrls);

    $migration->down();
    foreach ($originalTypes as $id => $ids) {
        expect(Category::findOrFail($id)->types()->pluck('types.id')->sort()->values()->all())->toBe($ids);
    }
    expect(Category::submittable()->count())->toBe(263);

    Category::where('slug', 'ai-agent')->delete();
    $migration->up();
    Log::shouldHaveReceived('warning')->with('Category merge CSV slug missing', ['slug' => 'ai-agent'])->once();
    expect(Category::where('slug', 'ai-agent')->exists())->toBeFalse();
});

it('renders old and target products once on the old page and preserves the target page query', function () {
    $target = Category::factory()->create(['slug' => 'target-tools']);
    $source = Category::factory()->create([
        'name' => 'Legacy Tools',
        'slug' => 'legacy-tools',
        'submittable' => false,
        'merge_target_slug' => $target->slug,
    ]);

    [$sourceOnly, $targetOnly, $shared, $unpublished] = Product::factory()->count(4)->create([
        'approved' => true,
        'is_published' => true,
    ])->all();
    $unpublished->update(['is_published' => false]);
    $source->products()->attach([$sourceOnly->id, $shared->id]);
    $target->products()->attach([$targetOnly->id, $shared->id, $unpublished->id]);

    $this->get($source->publicUrl())->assertOk()
        ->assertViewHas('regularProducts', fn ($products) => $products->pluck('id')->sort()->values()->all()
            === collect([$sourceOnly->id, $targetOnly->id, $shared->id])->sort()->values()->all())
        ->assertSee('<link rel="canonical" href="'.$source->publicUrl().'"', false);
    $this->get($target->publicUrl())->assertOk()
        ->assertViewHas('publishedProductCount', 2)
        ->assertViewHas('regularProducts', fn ($products) => ! $products->contains('id', $sourceOnly->id));

    $source->update(['canonical_slug' => $target->slug]);
    $this->get($source->publicUrl())->assertOk()
        ->assertSee('<link rel="canonical" href="'.$target->publicUrl().'"', false);
});

it('keeps existing hidden tags on the edit form and through an inline edit', function () {
    $owner = \App\Models\User::factory()->create();
    $type = Type::create(['name' => 'Software']);
    $retired = Category::factory()->create(['submittable' => false]);
    $available = Category::factory()->create(['submit_label' => 'New label']);
    $retired->types()->attach($type);
    $available->types()->attach($type);
    $product = Product::factory()->create(['user_id' => $owner->id, 'approved' => false]);
    $product->categories()->attach($retired);

    $this->actingAs($owner)->get(route('products.edit', $product))
        ->assertOk()
        ->assertViewHas('displayData', fn ($data) => in_array($retired->id, $data['current_categories'], true))
        ->assertViewHas('regularCategories', fn ($options) => $options->contains('id', $retired->id));
    $this->patchJson(route('products.inline-update', $product), ['field' => 'categories', 'value' => [$available->id]])
        ->assertOk();

    expect($product->categories()->pluck('categories.id')->sort()->values()->all())
        ->toBe([$retired->id, $available->id]);
});

it('removes a Software type created solely by the migration when rolling back', function () {
    $legacy = Type::create(['name' => 'Software Categories']);
    $category = Category::factory()->create();
    $category->types()->attach($legacy);

    $migration = require database_path('migrations/2026_11_21_000002_apply_category_merge_plan.php');
    $migration->up();

    expect($category->types()->pluck('name')->all())->toBe(['Software']);

    $migration->down();

    expect($category->types()->pluck('name')->all())->toBe(['Software Categories'])
        ->and(Type::where('name', 'Software')->exists())->toBeFalse();
});

it('offers the broader use cases on new submissions and keeps legacy use case URLs and tags', function () {
    $software = Type::create(['name' => 'Software']);
    $useCase = Type::create(['name' => 'Use Case']);
    $category = Category::factory()->create();
    $category->forceFill(['in_submission_plan' => true])->save();
    $category->types()->attach($software);
    $legacy = Category::create(['name' => 'Blog Writing', 'slug' => 'blog-writing']);
    $desktop = Category::create(['name' => 'Desktop Customization', 'slug' => 'desktop-customization']);
    $legacy->types()->attach($useCase);
    $desktop->types()->attach($useCase);
    $product = Product::factory()->create(['approved' => true, 'is_published' => true]);
    $product->categories()->attach($legacy);
    $originalUrl = $legacy->publicUrl();

    $migration = require database_path('migrations/2026_11_22_000001_add_broad_use_cases.php');
    $events = ['eloquent.saved: '.Category::class];
    \Illuminate\Support\Facades\Event::fakeFor(function () use ($migration, $events) {
        $migration->up();
        $migration->up();
        \Illuminate\Support\Facades\Event::assertNotDispatched($events[0]);
    }, $events);

    $broad = Category::where('slug', 'writing-editing')->firstOrFail();
    $product->categories()->attach($broad);

    $this->getJson('/api/categories')->assertOk()
        ->assertJsonCount(1, 'categories')
        ->assertJsonCount(13, 'useCases')
        ->assertJsonFragment(['id' => $desktop->id, 'name' => $desktop->name])
        ->assertJsonMissing(['name' => 'Blog Writing']);
    $this->get(route('products.create'))->assertOk()
        ->assertViewHas('useCaseCategories', fn ($options) => $options->count() === 13);

    $rule = Category::submissionOptionRule(true);
    expect(validator(['id' => $broad->id], ['id' => [$rule]])->passes())->toBeTrue()
        ->and(validator(['id' => $legacy->id], ['id' => [$rule]])->fails())->toBeTrue();
    expect(Category::submissionTypeIds(\App\Support\CategoryTypeRegistry::USE_CASE, curated: true))
        ->toHaveCount(13);

    expect($legacy->fresh()->publicUrl())->toBe($originalUrl);
    $this->get($originalUrl)->assertOk()->assertViewHas('publishedProductCount', 1);
    $this->get($broad->publicUrl())->assertOk()->assertViewHas('publishedProductCount', 1);
    expect($product->categories()->where('categories.id', $legacy->id)->exists())->toBeTrue();
});
