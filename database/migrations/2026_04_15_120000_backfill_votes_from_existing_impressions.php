<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('products')
            ->select('id', 'impressions', 'votes_count')
            ->orderBy('id')
            ->chunkById(500, function ($products) {
                foreach ($products as $product) {
                    $impressions = max(0, (int) ($product->impressions ?? 0));
                    $bonusVotes = intdiv($impressions, 4);

                    if ($bonusVotes < 1) {
                        continue;
                    }

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'votes_count' => max(1, (int) $product->votes_count) + $bonusVotes,
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('products')
            ->select('id', 'impressions', 'votes_count')
            ->orderBy('id')
            ->chunkById(500, function ($products) {
                foreach ($products as $product) {
                    $impressions = max(0, (int) ($product->impressions ?? 0));
                    $bonusVotes = intdiv($impressions, 4);

                    if ($bonusVotes < 1) {
                        continue;
                    }

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'votes_count' => max(1, (int) $product->votes_count - $bonusVotes),
                        ]);
                }
            });
    }
};
