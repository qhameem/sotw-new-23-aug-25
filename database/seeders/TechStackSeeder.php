<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TechStack;
use Illuminate\Support\Str;

class TechStackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $techStacks = [
            'WordPress',
            'Shopify',
            'Next.js',
            'Laravel',
            'React',
            'Vue.js',
            'Angular',
            'Svelte',
            'Ruby on Rails',
            'Django',
            'Flask',
            'Node.js',
            'PHP',
            'Python',
            'Ruby',
            'Go',
            'Java',
            'Swift',
            'Kotlin',
            'Flutter',
            'React Native',
        ];

        foreach ($techStacks as $tech) {
            TechStack::firstOrCreate([
                'slug' => Str::slug($tech),
            ], [
                'name' => $tech,
            ]);
        }
    }
}
