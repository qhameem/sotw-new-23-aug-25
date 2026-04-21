<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteAdsTable([
                'image_banner',
                'product_listing_card',
                'text_link',
                'html_snippet',
            ]);
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE ads MODIFY COLUMN type ENUM('image_banner', 'product_listing_card', 'text_link', 'html_snippet') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteAdsTable([
                'image_banner',
                'text_link',
                'html_snippet',
            ]);
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE ads MODIFY COLUMN type ENUM('image_banner', 'text_link', 'html_snippet') NOT NULL");
    }

    protected function rebuildSqliteAdsTable(array $allowedTypes): void
    {
        if (! Schema::hasTable('ads')) {
            return;
        }

        $columns = [
            'id',
            'internal_name',
            'type',
            'content',
            'tagline',
            'target_url',
            'open_in_new_tab',
            'is_active',
            'start_date',
            'end_date',
            'created_at',
            'updated_at',
            'target_countries',
            'target_routes',
            'target_category_ids',
            'audience_scope',
            'device_types',
            'weight',
            'priority',
            'is_house_ad',
            'impressions_count',
            'clicks_count',
            'manages_own_image',
        ];
        $typeList = implode("', '", $allowedTypes);
        $columnList = implode(', ', $columns);

        DB::statement('PRAGMA foreign_keys=OFF');
        DB::statement("CREATE TABLE ads_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            internal_name VARCHAR NOT NULL,
            type TEXT NOT NULL CHECK(type IN ('{$typeList}')),
            content TEXT NOT NULL,
            tagline VARCHAR NULL,
            target_url VARCHAR NULL,
            open_in_new_tab TINYINT(1) NOT NULL DEFAULT 1,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            start_date DATETIME NULL,
            end_date DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            target_countries TEXT NULL,
            target_routes TEXT NULL,
            target_category_ids TEXT NULL,
            audience_scope VARCHAR NOT NULL DEFAULT 'all',
            device_types TEXT NULL,
            weight INTEGER NOT NULL DEFAULT 1,
            priority INTEGER NOT NULL DEFAULT 0,
            is_house_ad TINYINT(1) NOT NULL DEFAULT 0,
            impressions_count INTEGER NOT NULL DEFAULT 0,
            clicks_count INTEGER NOT NULL DEFAULT 0,
            manages_own_image TINYINT(1) NOT NULL DEFAULT 1
        )");
        DB::statement("INSERT INTO ads_new ({$columnList}) SELECT {$columnList} FROM ads");
        DB::statement('DROP TABLE ads');
        DB::statement('ALTER TABLE ads_new RENAME TO ads');
        DB::statement('PRAGMA foreign_keys=ON');
    }
};
