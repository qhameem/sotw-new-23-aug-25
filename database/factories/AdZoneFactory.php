<?php

namespace Database\Factories;

use App\Models\AdZone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AdZone>
 */
class AdZoneFactory extends Factory
{
    protected $model = AdZone::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'render_location' => fake()->sentence(),
            'placement_type' => 'sidebar',
            'supported_ad_types' => ['image_banner', 'product_listing_card', 'text_link'],
            'max_ads' => 1,
            'rotation_mode' => 'random',
            'device_scope' => 'all',
            'fallback_mode' => 'empty',
            'display_after_nth_product' => null,
        ];
    }
}
