<?php

namespace Database\Factories;

use App\Models\Ad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ad>
 */
class AdFactory extends Factory
{
    protected $model = Ad::class;

    public function definition(): array
    {
        return [
            'internal_name' => fake()->sentence(3),
            'type' => 'image_banner',
            'content' => 'ads/' . fake()->uuid() . '.png',
            'tagline' => fake()->sentence(),
            'target_url' => fake()->url(),
            'open_in_new_tab' => true,
            'is_active' => true,
            'start_date' => null,
            'end_date' => null,
            'audience_scope' => 'all',
            'weight' => 1,
            'priority' => 0,
            'is_house_ad' => false,
            'impressions_count' => 0,
            'clicks_count' => 0,
            'manages_own_image' => true,
        ];
    }
}
