<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->json('target_countries')->nullable()->after('end_date');
            $table->json('target_routes')->nullable()->after('target_countries');
            $table->json('target_category_ids')->nullable()->after('target_routes');
            $table->string('audience_scope')->default('all')->after('target_category_ids');
            $table->json('device_types')->nullable()->after('audience_scope');
            $table->unsignedInteger('weight')->default(1)->after('device_types');
            $table->unsignedInteger('priority')->default(0)->after('weight');
            $table->boolean('is_house_ad')->default(false)->after('priority');
            $table->unsignedBigInteger('impressions_count')->default(0)->after('is_house_ad');
            $table->unsignedBigInteger('clicks_count')->default(0)->after('impressions_count');
            $table->boolean('manages_own_image')->default(true)->after('clicks_count');
        });

        $productLogos = DB::table('products')
            ->pluck('logo')
            ->filter()
            ->map(function ($logo) {
                return $this->normalizeStorageValue((string) $logo);
            })
            ->merge(
                DB::table('products')
                    ->pluck('logo')
                    ->filter()
                    ->map(fn ($logo) => trim((string) $logo))
            )
            ->unique()
            ->values()
            ->all();

        DB::table('ads')
            ->select(['id', 'type', 'content'])
            ->orderBy('id')
            ->get()
            ->each(function ($ad) use ($productLogos) {
                if ($ad->type !== 'image_banner' || ! is_string($ad->content)) {
                    return;
                }

                $updates = [];
                $normalized = $this->normalizeStorageValue($ad->content);

                if ($normalized !== null && $normalized !== trim($ad->content)) {
                    $updates['content'] = $normalized;
                }

                $comparisonValue = $normalized ?? trim($ad->content);

                if (in_array($comparisonValue, $productLogos, true)) {
                    $updates['manages_own_image'] = false;
                }

                if ($updates !== []) {
                    DB::table('ads')->where('id', $ad->id)->update($updates);
                }
            });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn([
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
            ]);
        });
    }

    protected function normalizeStorageValue(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '/storage/')) {
            return ltrim(substr($value, strlen('/storage/')), '/');
        }

        if (str_starts_with($value, 'storage/')) {
            return ltrim(substr($value, strlen('storage/')), '/');
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $path = parse_url($value, PHP_URL_PATH);

            if (is_string($path) && str_starts_with($path, '/storage/')) {
                return ltrim(substr($path, strlen('/storage/')), '/');
            }

            return trim($value);
        }

        return str_contains($value, '://') ? trim($value) : ltrim($value, '/');
    }
};
