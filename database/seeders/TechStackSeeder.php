<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TechStackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $techStacks = [
            // Programming Languages
            ['name' => 'PHP'],
            ['name' => 'JavaScript'],
            ['name' => 'Python'],
            ['name' => 'Ruby'],
            ['name' => 'Java'],
            ['name' => 'Go'],
            ['name' => 'Rust'],
            ['name' => 'TypeScript'],
            ['name' => 'Swift'],
            ['name' => 'Kotlin'],

            // Frontend Frameworks & Libraries
            ['name' => 'React'],
            ['name' => 'Vue.js'],
            ['name' => 'Angular'],
            ['name' => 'Svelte'],
            ['name' => 'jQuery'],
            ['name' => 'Next.js'],
            ['name' => 'Nuxt.js'],
            ['name' => 'Gatsby'],
            ['name' => 'Bootstrap'],
            ['name' => 'Tailwind CSS'],
            ['name' => 'Material UI'],

            // Backend Frameworks & Environments
            ['name' => 'Laravel'],
            ['name' => 'Symfony'],
            ['name' => 'Node.js'],
            ['name' => 'Express'],
            ['name' => 'Django'],
            ['name' => 'Flask'],
            ['name' => 'Ruby on Rails'],
            ['name' => 'Spring'],

            // Databases
            ['name' => 'MySQL'],
            ['name' => 'PostgreSQL'],
            ['name' => 'SQLite'],
            ['name' => 'MongoDB'],
            ['name' => 'Redis'],
            ['name' => 'Firebase'],

            // CMS
            ['name' => 'WordPress'],
            ['name' => 'Shopify'],
            ['name' => 'Magento'],
            ['name' => 'Drupal'],
            ['name' => 'Joomla'],
            ['name' => 'Webflow'],

            // Web Servers
            ['name' => 'Nginx'],
            ['name' => 'Apache'],

            // Cloud & DevOps
            ['name' => 'AWS'],
            ['name' => 'Google Cloud'],
            ['name' => 'Azure'],
            ['name' => 'Docker'],
            ['name' => 'Kubernetes'],
            ['name' => 'Vercel'],
            ['name' => 'Netlify'],

            // Analytics & Marketing
            ['name' => 'Google Analytics'],
            ['name' => 'Segment'],
            ['name' => 'Mixpanel'],
            ['name' => 'Hotjar'],
            ['name' => 'Stripe'],
            ['name' => 'PayPal'],

            // Other
            ['name' => 'GraphQL'],
            ['name' => 'REST'],
        ];

        foreach ($techStacks as &$techStack) {
            $techStack['slug'] = Str::slug($techStack['name']);
            DB::table('tech_stacks')->updateOrInsert(['name' => $techStack['name']], $techStack);
        }
    }
}
