<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_zones', function (Blueprint $table) {
            $table->string('render_location')->nullable()->after('description');
            $table->string('placement_type')->default('other')->after('render_location');
            $table->json('supported_ad_types')->nullable()->after('placement_type');
            $table->unsignedInteger('max_ads')->default(1)->after('supported_ad_types');
            $table->string('rotation_mode')->default('random')->after('max_ads');
            $table->string('device_scope')->default('all')->after('rotation_mode');
            $table->string('fallback_mode')->default('empty')->after('device_scope');
        });

        $zones = [
            [
                'name' => 'Sponsors',
                'slug' => 'sponsors',
                'description' => 'Sidebar sponsor section',
                'render_location' => 'Right sidebar partner list',
                'placement_type' => 'sidebar',
                'supported_ad_types' => json_encode(['image_banner', 'product_listing_card']),
                'max_ads' => 6,
                'rotation_mode' => 'weighted',
                'device_scope' => 'all',
                'fallback_mode' => 'empty',
                'display_after_nth_product' => null,
            ],
            [
                'name' => 'Sidebar Top',
                'slug' => 'sidebar-top',
                'description' => 'Top slot in the right sidebar',
                'render_location' => 'Top of the right sidebar',
                'placement_type' => 'sidebar',
                'supported_ad_types' => json_encode(['image_banner', 'product_listing_card', 'text_link']),
                'max_ads' => 1,
                'rotation_mode' => 'priority',
                'device_scope' => 'all',
                'fallback_mode' => 'empty',
                'display_after_nth_product' => null,
            ],
            [
                'name' => 'Header Above Calendar',
                'slug' => 'header-above-calendar',
                'description' => 'Header slot above the calendar navigation',
                'render_location' => 'Above the homepage calendar header',
                'placement_type' => 'header',
                'supported_ad_types' => json_encode(['image_banner', 'product_listing_card', 'text_link', 'html_snippet']),
                'max_ads' => 1,
                'rotation_mode' => 'priority',
                'device_scope' => 'all',
                'fallback_mode' => 'empty',
                'display_after_nth_product' => null,
            ],
            [
                'name' => 'Below Product Listing',
                'slug' => 'below-product-listing',
                'description' => 'Inline slot rendered inside product lists',
                'render_location' => 'Inside the product list after a configured position',
                'placement_type' => 'in_feed',
                'supported_ad_types' => json_encode(['image_banner', 'product_listing_card', 'text_link', 'html_snippet']),
                'max_ads' => 1,
                'rotation_mode' => 'weighted',
                'device_scope' => 'all',
                'fallback_mode' => 'empty',
                'display_after_nth_product' => 3,
            ],
        ];

        foreach ($zones as $zone) {
            $existing = DB::table('ad_zones')->where('slug', $zone['slug'])->first();

            if ($existing) {
                DB::table('ad_zones')->where('id', $existing->id)->update([
                    'name' => $zone['name'],
                    'description' => $zone['description'],
                    'render_location' => $zone['render_location'],
                    'placement_type' => $zone['placement_type'],
                    'supported_ad_types' => $zone['supported_ad_types'],
                    'max_ads' => $zone['max_ads'],
                    'rotation_mode' => $zone['rotation_mode'],
                    'device_scope' => $zone['device_scope'],
                    'fallback_mode' => $zone['fallback_mode'],
                    'display_after_nth_product' => $zone['display_after_nth_product'] ?? $existing->display_after_nth_product,
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('ad_zones')->insert([
                ...$zone,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('ad_zones', function (Blueprint $table) {
            $table->dropColumn([
                'render_location',
                'placement_type',
                'supported_ad_types',
                'max_ads',
                'rotation_mode',
                'device_scope',
                'fallback_mode',
            ]);
        });
    }
};
