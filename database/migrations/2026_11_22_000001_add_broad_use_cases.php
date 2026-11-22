<?php

use App\Models\Category;
use App\Support\CategoryTypeRegistry;
use Database\Seeders\BroadUseCaseSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (Category::submissionTypeIds(CategoryTypeRegistry::USE_CASE)->isNotEmpty()) {
            app(BroadUseCaseSeeder::class)->run();
        }
    }

    public function down(): void
    {
        // Keep published category URLs and product tags when rolling back.
    }
};
