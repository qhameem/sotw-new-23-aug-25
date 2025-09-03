<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoryKeywordsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categoriesKeywords = [
            'Productivity' => ['task', 'project', 'workflow', 'collaboration', 'organization'],
            'Design' => ['graphics', 'vector', 'illustration', 'photoshop', 'sketch'],
            'Developer Tools' => ['api', 'debug', 'ide', 'compiler', 'git'],
            'SaaS' => ['subscription', 'cloud', 'web-based', 'hosted', 'software as a service'],
            'Marketing' => ['seo', 'analytics', 'email', 'social media', 'campaign'],
            // Pricing Models
            'Freemium' => ['free', 'premium', 'upgrade', 'paid plan', 'basic'],
            'Free' => ['free', 'no cost', 'open source', 'public'],
            'One-time Payment' => ['lifetime', 'one-time', 'single payment', 'purchase'],
            'Subscription' => ['monthly', 'yearly', 'annual', 'recurring', 'plan'],
            'In-App Purchases' => ['iap', 'in-app', 'credits', 'tokens', 'unlock'],
            'Pay-as-you-go' => ['usage-based', 'metered', 'payg', 'per use'],
            'Credit-based' => ['credits', 'tokens', 'prepaid', 'packs'],
            'Custom' => ['contact us', 'quote', 'enterprise', 'custom plan'],
        ];

        foreach ($categoriesKeywords as $categoryName => $keywords) {
            $category = Category::where('name', $categoryName)->first();
            if ($category) {
                $category->keywords = json_encode($keywords);
                $category->save();
            }
        }
    }
}
