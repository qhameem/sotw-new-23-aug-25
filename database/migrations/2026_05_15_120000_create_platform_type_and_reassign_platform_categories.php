<?php

use App\Support\CategoryTypeRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $platformTypeId = DB::table('types')->where('name', 'Platform')->value('id');

        if (!$platformTypeId) {
            $platformTypeId = DB::table('types')->insertGetId([
                'name' => 'Platform',
                'description' => 'Where the product runs or is available.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $softwareTypeIds = DB::table('types')
            ->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::SOFTWARE))
            ->pluck('id');

        $platformCategoryIds = DB::table('categories')
            ->whereIn(DB::raw('LOWER(name)'), CategoryTypeRegistry::platformCategoryNames())
            ->pluck('id');

        foreach ($platformCategoryIds as $categoryId) {
            DB::table('category_types')->updateOrInsert(
                [
                    'category_id' => $categoryId,
                    'type_id' => $platformTypeId,
                ],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        if ($softwareTypeIds->isNotEmpty() && $platformCategoryIds->isNotEmpty()) {
            DB::table('category_types')
                ->whereIn('category_id', $platformCategoryIds)
                ->whereIn('type_id', $softwareTypeIds)
                ->delete();
        }
    }

    public function down(): void
    {
        $platformTypeId = DB::table('types')->where('name', 'Platform')->value('id');
        $softwareTypeId = DB::table('types')
            ->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::SOFTWARE))
            ->value('id');

        if (!$platformTypeId) {
            return;
        }

        $platformCategoryIds = DB::table('categories')
            ->whereIn(DB::raw('LOWER(name)'), CategoryTypeRegistry::platformCategoryNames())
            ->pluck('id');

        if ($softwareTypeId && $platformCategoryIds->isNotEmpty()) {
            $now = now();

            foreach ($platformCategoryIds as $categoryId) {
                DB::table('category_types')->updateOrInsert(
                    [
                        'category_id' => $categoryId,
                        'type_id' => $softwareTypeId,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        DB::table('category_types')
            ->where('type_id', $platformTypeId)
            ->whereIn('category_id', $platformCategoryIds)
            ->delete();

        $remainingPlatformLinks = DB::table('category_types')->where('type_id', $platformTypeId)->exists();

        if (!$remainingPlatformLinks) {
            DB::table('types')->where('id', $platformTypeId)->delete();
        }
    }
};
