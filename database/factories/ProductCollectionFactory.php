<?php

namespace Database\Factories;

use App\Models\ProductCollection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductCollection>
 */
class ProductCollectionFactory extends Factory
{
    protected $model = ProductCollection::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'user_id' => User::factory(),
            'name' => Str::title($name),
            'slug' => fake()->unique()->slug(),
            'visibility' => ProductCollection::VISIBILITY_PUBLIC,
        ];
    }
}
