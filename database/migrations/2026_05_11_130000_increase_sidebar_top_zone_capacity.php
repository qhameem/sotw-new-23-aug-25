<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ad_zones')
            ->where('slug', 'sidebar-top')
            ->update([
                'max_ads' => 6,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('ad_zones')
            ->where('slug', 'sidebar-top')
            ->where('max_ads', 6)
            ->update([
                'max_ads' => 1,
                'updated_at' => now(),
            ]);
    }
};
