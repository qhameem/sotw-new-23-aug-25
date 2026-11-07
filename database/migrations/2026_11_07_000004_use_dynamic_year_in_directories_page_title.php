<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('custom_pages')->where('slug', 'directories')
            ->where('title', 'like', '%(2026)%')->update([
                'title' => DB::raw("REPLACE(title, '(2026)', '([year])')"),
            ]);
    }

    public function down(): void
    {
        // Preserve authored content and shortcodes when rolling back.
    }
};
