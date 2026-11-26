<?php

use App\Services\StartupDirectoryArchiveCategories;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $namesByCategory = [];

        foreach (StartupDirectoryArchiveCategories::BY_NAME as $name => $category) {
            $namesByCategory[$category][] = $name;
        }

        DB::transaction(function () use ($namesByCategory): void {
            foreach ($namesByCategory as $category => $names) {
                DB::table('startup_directories')
                    ->whereNull('category')
                    ->whereIn('name', $names)
                    ->update(['category' => $category]);
            }
        });
    }

    public function down(): void
    {
        // Preserve classifications and any subsequent editorial changes.
    }
};
