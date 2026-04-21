<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdZone;

class AdZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        collect([
            [
                'name' => 'Sponsors',
                'slug' => 'sponsors',
                'description' => 'Sidebar sponsor section',
                'render_location' => 'Right sidebar partner list',
                'placement_type' => 'sidebar',
                'supported_ad_types' => ['image_banner', 'product_listing_card'],
                'max_ads' => 6,
                'rotation_mode' => 'weighted',
                'device_scope' => 'all',
                'fallback_mode' => 'empty',
            ],
            [
                'name' => 'Sidebar Top',
                'slug' => 'sidebar-top',
                'description' => 'Top of the right sidebar',
                'render_location' => 'Top of the right sidebar',
                'placement_type' => 'sidebar',
                'supported_ad_types' => ['image_banner', 'product_listing_card', 'text_link'],
                'max_ads' => 1,
                'rotation_mode' => 'priority',
                'device_scope' => 'all',
                'fallback_mode' => 'empty',
            ],
            [
                'name' => 'Header Above Calendar',
                'slug' => 'header-above-calendar',
                'description' => 'Header slot above the calendar navigation',
                'render_location' => 'Above the homepage calendar',
                'placement_type' => 'header',
                'supported_ad_types' => ['image_banner', 'product_listing_card', 'text_link', 'html_snippet'],
                'max_ads' => 1,
                'rotation_mode' => 'priority',
                'device_scope' => 'all',
                'fallback_mode' => 'empty',
            ],
            [
                'name' => 'Below Product Listing',
                'slug' => 'below-product-listing',
                'description' => 'Inline slot inside product lists',
                'render_location' => 'Inside the product list after a configured position',
                'placement_type' => 'in_feed',
                'supported_ad_types' => ['image_banner', 'product_listing_card', 'text_link', 'html_snippet'],
                'max_ads' => 1,
                'rotation_mode' => 'weighted',
                'device_scope' => 'all',
                'fallback_mode' => 'empty',
                'display_after_nth_product' => 3,
            ],
        ])->each(function (array $zone) {
            AdZone::updateOrCreate(
                ['slug' => $zone['slug']],
                $zone
            );
        });
    }
}
