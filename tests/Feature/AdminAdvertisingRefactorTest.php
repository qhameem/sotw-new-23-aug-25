<?php

use App\Models\Ad;
use App\Models\AdZone;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\AdDeliveryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function makeAdminUser(): User
{
    $role = Role::firstOrCreate(['name' => 'admin']);
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('admin can create a standard image ad and save its tagline', function () {
    Storage::fake('public');
    $admin = makeAdminUser();
    $zone = AdZone::factory()->create([
        'slug' => 'standard-zone',
        'supported_ad_types' => ['image_banner'],
    ]);

    $response = $this->actingAs($admin)->post(route('admin.ads.store'), [
        'template' => 'custom',
        'internal_name' => 'Launch Banner',
        'tagline' => 'Shipping today',
        'type' => 'image_banner',
        'content_image' => UploadedFile::fake()->image('launch-banner.png'),
        'target_url' => 'https://example.com/launch',
        'ad_zones' => [$zone->id],
        'is_active' => 1,
        'open_in_new_tab' => 1,
    ]);

    $response->assertRedirect(route('admin.advertising.index', ['tab' => 'ads']));

    $ad = Ad::firstOrFail();

    expect($ad->tagline)->toBe('Shipping today')
        ->and($ad->content)->toStartWith('ads/')
        ->and($ad->manages_own_image)->toBeTrue();

    Storage::disk('public')->assertExists($ad->content);
});

test('admin can create a product listing card ad', function () {
    Storage::fake('public');
    $admin = makeAdminUser();
    $zone = AdZone::factory()->create([
        'slug' => 'listing-card-zone',
        'supported_ad_types' => ['product_listing_card'],
    ]);

    $response = $this->actingAs($admin)->post(route('admin.ads.store'), [
        'template' => 'product_listing_card',
        'internal_name' => 'Acme Analytics',
        'tagline' => 'Get product insights in minutes',
        'type' => 'product_listing_card',
        'content_image' => UploadedFile::fake()->image('acme-logo.png'),
        'target_url' => 'https://example.com/acme',
        'ad_zones' => [$zone->id],
        'is_active' => 1,
        'open_in_new_tab' => 1,
    ]);

    $response->assertRedirect(route('admin.advertising.index', ['tab' => 'ads']));

    $ad = Ad::firstOrFail();

    expect($ad->type)->toBe('product_listing_card')
        ->and($ad->tagline)->toBe('Get product insights in minutes')
        ->and($ad->content)->toStartWith('ads/')
        ->and($ad->image_url)->not->toBeNull();
});

test('admin can create a sponsor ad from an existing product and reuse product link data', function () {
    Storage::fake('public');
    $admin = makeAdminUser();
    $zone = AdZone::query()->where('slug', 'sponsors')->firstOrFail();
    $zone->update([
        'supported_ad_types' => ['image_banner'],
    ]);
    Storage::disk('public')->put('products/product-logo.png', 'logo');
    $product = Product::factory()->create([
        'name' => 'Product Sponsor',
        'tagline' => 'Trusted by founders',
        'link' => 'https://product.example.com',
        'logo' => 'products/product-logo.png',
    ]);

    $this->actingAs($admin)->post(route('admin.ads.store'), [
        'template' => 'sponsor',
        'product_id' => $product->id,
        'type' => 'image_banner',
        'ad_zones' => [$zone->id],
        'is_active' => 1,
        'open_in_new_tab' => 1,
    ])->assertRedirect(route('admin.advertising.index', ['tab' => 'ads']));

    $ad = Ad::firstOrFail();

    expect($ad->internal_name)->toBe('Product Sponsor')
        ->and($ad->tagline)->toBe('Trusted by founders')
        ->and($ad->target_url)->toBe('https://product.example.com')
        ->and($ad->content)->toBe('products/product-logo.png')
        ->and($ad->manages_own_image)->toBeFalse();
});

test('editing an image ad without replacing the file keeps the existing image', function () {
    Storage::fake('public');
    $admin = makeAdminUser();
    $zone = AdZone::factory()->create([
        'supported_ad_types' => ['image_banner'],
    ]);
    Storage::disk('public')->put('ads/existing.png', 'old-file');
    $ad = Ad::factory()->create([
        'type' => 'image_banner',
        'content' => 'ads/existing.png',
        'manages_own_image' => true,
    ]);
    $ad->adZones()->sync([$zone->id]);

    $this->actingAs($admin)->put(route('admin.ads.update', $ad), [
        'template' => 'custom',
        'internal_name' => 'Updated Name',
        'tagline' => 'Still the same file',
        'type' => 'image_banner',
        'target_url' => 'https://example.com/updated',
        'ad_zones' => [$zone->id],
        'is_active' => 1,
        'open_in_new_tab' => 1,
    ])->assertRedirect(route('admin.advertising.index', ['tab' => 'ads']));

    $ad->refresh();

    expect($ad->content)->toBe('ads/existing.png');
    Storage::disk('public')->assertExists('ads/existing.png');
});

test('editing an image ad with a replacement deletes the old file', function () {
    Storage::fake('public');
    $admin = makeAdminUser();
    $zone = AdZone::factory()->create([
        'supported_ad_types' => ['image_banner'],
    ]);
    Storage::disk('public')->put('ads/existing.png', 'old-file');
    $ad = Ad::factory()->create([
        'type' => 'image_banner',
        'content' => 'ads/existing.png',
        'manages_own_image' => true,
    ]);
    $ad->adZones()->sync([$zone->id]);

    $this->actingAs($admin)->put(route('admin.ads.update', $ad), [
        'template' => 'custom',
        'internal_name' => 'Updated Name',
        'tagline' => 'New file',
        'type' => 'image_banner',
        'content_image' => UploadedFile::fake()->image('replacement.png'),
        'target_url' => 'https://example.com/updated',
        'ad_zones' => [$zone->id],
        'is_active' => 1,
        'open_in_new_tab' => 1,
    ])->assertRedirect(route('admin.advertising.index', ['tab' => 'ads']));

    $ad->refresh();

    expect($ad->content)->not->toBe('ads/existing.png');
    Storage::disk('public')->assertMissing('ads/existing.png');
    Storage::disk('public')->assertExists($ad->content);
});

test('a single ad can be assigned to multiple zones', function () {
    Storage::fake('public');
    $admin = makeAdminUser();
    $primary = AdZone::factory()->create(['supported_ad_types' => ['image_banner']]);
    $secondary = AdZone::factory()->create(['supported_ad_types' => ['image_banner']]);

    $this->actingAs($admin)->post(route('admin.ads.store'), [
        'template' => 'custom',
        'internal_name' => 'Multi-zone Banner',
        'type' => 'image_banner',
        'content_image' => UploadedFile::fake()->image('multi-zone.png'),
        'target_url' => 'https://example.com',
        'ad_zones' => [$primary->id, $secondary->id],
        'is_active' => 1,
        'open_in_new_tab' => 1,
    ])->assertRedirect(route('admin.advertising.index', ['tab' => 'ads']));

    $ad = Ad::firstOrFail();

    expect($ad->adZones()->count())->toBe(2);
});

test('delivery service filters inactive and scheduled ads and falls back to house ads when configured', function () {
    $zone = AdZone::query()->where('slug', 'sponsors')->firstOrFail();
    $zone->update([
        'supported_ad_types' => ['image_banner'],
        'rotation_mode' => 'priority',
        'fallback_mode' => 'house_ads',
    ]);
    $activeAd = Ad::factory()->create([
        'priority' => 20,
        'start_date' => now()->subHour(),
        'end_date' => now()->addHour(),
    ]);
    $futureAd = Ad::factory()->create([
        'priority' => 50,
        'start_date' => now()->addDay(),
    ]);
    $inactiveAd = Ad::factory()->create([
        'is_active' => false,
    ]);
    $houseAd = Ad::factory()->create([
        'is_house_ad' => true,
        'priority' => 5,
    ]);

    $zone->ads()->sync([$activeAd->id, $futureAd->id, $inactiveAd->id]);

    $ads = app(AdDeliveryService::class)->forZone($zone, [
        'route_name' => 'home',
        'page_type' => 'home',
        'audience_scope' => 'guest',
        'device_type' => 'desktop',
    ]);

    expect($ads->pluck('id')->all())->toBe([$activeAd->id]);

    $zone->ads()->sync([$futureAd->id, $inactiveAd->id, $houseAd->id]);

    $fallbackAds = app(AdDeliveryService::class)->forZone($zone, [
        'route_name' => 'home',
        'page_type' => 'home',
        'audience_scope' => 'guest',
        'device_type' => 'desktop',
    ]);

    expect($fallbackAds->pluck('id')->all())->toBe([$houseAd->id]);
});

test('click tracking increments counts and adds outbound utm parameters', function () {
    $ad = Ad::factory()->create([
        'target_url' => 'https://example.com/pricing?ref=abc',
    ]);

    $response = $this->get(route('ads.click', ['ad' => $ad, 'zone' => 'sidebar-top']));

    $response->assertRedirect();
    $ad->refresh();

    expect($ad->clicks_count)->toBe(1);
    expect($response->headers->get('Location'))
        ->toContain('utm_source=software-on-the-web')
        ->toContain('utm_medium=advertising')
        ->toContain('utm_campaign=sidebar-top');
});

test('zone deletion is blocked while ads are attached and empty zones fail gracefully', function () {
    $admin = makeAdminUser();
    $zone = AdZone::factory()->create();
    $ad = Ad::factory()->create();
    $zone->ads()->sync([$ad->id]);

    $this->actingAs($admin)->delete(route('admin.ad-zones.destroy', $zone))
        ->assertRedirect(route('admin.advertising.index', ['tab' => 'ad_zones']));

    expect(AdZone::find($zone->id))->not->toBeNull();

    $emptyZone = AdZone::factory()->create();
    $ads = app(AdDeliveryService::class)->forZone($emptyZone, [
        'route_name' => 'home',
        'page_type' => 'home',
        'audience_scope' => 'guest',
        'device_type' => 'desktop',
    ]);

    expect($ads)->toHaveCount(0);
});

test('admin advertising dashboard shows effective statuses', function () {
    $admin = makeAdminUser();
    $zone = AdZone::factory()->create(['supported_ad_types' => ['image_banner']]);
    $activeAd = Ad::factory()->create(['internal_name' => 'Active Ad']);
    $scheduledAd = Ad::factory()->create(['internal_name' => 'Scheduled Ad', 'start_date' => now()->addDay()]);
    $inactiveAd = Ad::factory()->create(['internal_name' => 'Inactive Ad', 'is_active' => false]);
    $zone->ads()->sync([$activeAd->id, $scheduledAd->id, $inactiveAd->id]);

    $response = $this->actingAs($admin)->get(route('admin.advertising.index', ['tab' => 'ads']));

    $response->assertOk()
        ->assertSee('Active Ad')
        ->assertSee('Scheduled Ad')
        ->assertSee('Inactive Ad')
        ->assertSee('Scheduled')
        ->assertSee('Inactive');
});

test('sidebar top ads render on the home page sidebar', function () {
    $zone = AdZone::query()->where('slug', 'sidebar-top')->firstOrFail();
    $zone->update([
        'supported_ad_types' => ['image_banner'],
        'rotation_mode' => 'priority',
    ]);

    $ad = Ad::factory()->create([
        'internal_name' => 'Sidebar Launch',
        'type' => 'image_banner',
        'content' => 'ads/sidebar-launch.png',
        'target_url' => 'https://example.com/sidebar',
        'priority' => 100,
    ]);

    $zone->ads()->sync([$ad->id]);

    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Sidebar Launch')
        ->assertSee('sidebar-top');
});

test('sidebar top product listing card ads render on the home page sidebar', function () {
    $zone = AdZone::query()->where('slug', 'sidebar-top')->firstOrFail();
    $zone->update([
        'supported_ad_types' => ['image_banner', 'product_listing_card'],
        'rotation_mode' => 'priority',
    ]);

    $ad = Ad::factory()->create([
        'internal_name' => 'Compact Product Ad',
        'tagline' => 'Looks like a listing card',
        'type' => 'product_listing_card',
        'content' => 'ads/compact-product.png',
        'target_url' => 'https://example.com/compact-product',
        'priority' => 100,
    ]);

    $zone->ads()->sync([$ad->id]);

    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Compact Product Ad')
        ->assertSee('Looks like a listing card');
});
