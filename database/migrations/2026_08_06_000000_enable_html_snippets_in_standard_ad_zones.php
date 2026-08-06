<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ad_zones')
            ->whereIn('slug', ['sponsors', 'sidebar-top'])
            ->orderBy('id')
            ->each(function ($zone): void {
                $types = json_decode($zone->supported_ad_types ?? '[]', true) ?: [];

                foreach (['text_link', 'html_snippet'] as $type) {
                    if (! in_array($type, $types, true)) {
                        $types[] = $type;
                    }
                }

                DB::table('ad_zones')->where('id', $zone->id)->update([
                    'supported_ad_types' => json_encode(array_values($types)),
                ]);
            });
    }

    public function down(): void
    {
        // Existing installations may already support these types; do not remove user configuration.
    }
};
