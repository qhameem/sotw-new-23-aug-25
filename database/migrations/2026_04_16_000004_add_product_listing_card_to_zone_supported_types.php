<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ad_zones')
            ->select(['id', 'supported_ad_types'])
            ->orderBy('id')
            ->get()
            ->each(function ($zone) {
                $supportedTypes = json_decode($zone->supported_ad_types ?? '[]', true);

                if (! is_array($supportedTypes)) {
                    $supportedTypes = [];
                }

                if (! in_array('product_listing_card', $supportedTypes, true)) {
                    $supportedTypes[] = 'product_listing_card';
                }

                DB::table('ad_zones')
                    ->where('id', $zone->id)
                    ->update([
                        'supported_ad_types' => json_encode(array_values(array_unique($supportedTypes))),
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('ad_zones')
            ->select(['id', 'supported_ad_types'])
            ->orderBy('id')
            ->get()
            ->each(function ($zone) {
                $supportedTypes = json_decode($zone->supported_ad_types ?? '[]', true);

                if (! is_array($supportedTypes)) {
                    $supportedTypes = [];
                }

                $supportedTypes = array_values(array_filter($supportedTypes, fn (string $type) => $type !== 'product_listing_card'));

                DB::table('ad_zones')
                    ->where('id', $zone->id)
                    ->update([
                        'supported_ad_types' => json_encode($supportedTypes),
                    ]);
            });
    }
};
