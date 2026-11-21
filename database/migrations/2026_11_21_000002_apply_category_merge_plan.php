<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $plan = $this->plan();

        DB::transaction(function () use ($plan) {
            $typeIds = DB::table('types')->pluck('id', 'name');
            $legacyId = $typeIds->get('Software Categories');
            $matchedSlugs = [];

            Category::with('types')
                ->where(function ($query) use ($plan, $legacyId) {
                    $query->whereIn('slug', array_keys($plan));
                    if ($legacyId !== null) {
                        $query->orWhereHas('types', fn ($types) => $types->whereKey($legacyId));
                    }
                })
                ->chunkById(100, function ($categories) use ($plan, $typeIds, $legacyId, &$matchedSlugs) {
                    foreach ($categories as $category) {
                        $row = $plan[$category->slug] ?? null;
                        $action = $row['Action'] ?? null;
                        $retype = $action === 'RETYPE to Platform';
                        $originalTypes = $category->types->pluck('name')->all();
                        $originalIds = $category->types->modelKeys();

                        if ($row !== null) {
                            $matchedSlugs[$category->slug] = true;
                            DB::table('categories')->where('id', $category->id)->update([
                                'submittable' => $action !== 'MERGE',
                                'in_submission_plan' => true,
                                'merge_target_slug' => $action === 'MERGE' ? $row['Target Slug'] : null,
                                'submit_label' => $action === 'KEEP (rename)' ? $row['Target Name'] : null,
                                'legacy_page_type' => $retype ? $category->pageType() : null,
                            ]);
                        }

                        if ($retype || in_array($legacyId, $originalIds, true)) {
                            $newTypeName = $retype ? 'Platform' : 'Software';
                            $createdType = ! $typeIds->has($newTypeName);
                            $newTypeId = $this->typeId($typeIds, $newTypeName);
                            $newIds = $retype ? [$newTypeId] : array_unique(array_map(
                                fn ($id) => $id === $legacyId ? $newTypeId : $id,
                                $originalIds,
                            ));

                            $pivots = DB::table('category_types')->where('category_id', $category->id)->get();
                            DB::table('category_merge_type_backups')->insert([
                                'category_id' => $category->id,
                                'slug' => $category->slug,
                                'original_types' => json_encode($originalTypes, JSON_THROW_ON_ERROR),
                                'original_pivots' => $pivots->toJson(JSON_THROW_ON_ERROR),
                                'created_type_id' => $createdType ? $newTypeId : null,
                            ]);
                            Log::info('Category merge original types', ['slug' => $category->slug, 'types' => $originalTypes]);
                            $category->types()->sync($newIds);
                        }
                    }
                });

            foreach (array_diff_key($plan, $matchedSlugs) as $slug => $row) {
                Log::warning('Category merge CSV slug missing', ['slug' => $slug]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $backups = DB::table('category_merge_type_backups');
            $createdIds = (clone $backups)->whereNotNull('created_type_id')->pluck('created_type_id');

            foreach ($backups->orderBy('category_id')->cursor() as $backup) {
                DB::table('category_types')->where('category_id', $backup->category_id)->delete();
                $pivots = json_decode($backup->original_pivots, true, flags: JSON_THROW_ON_ERROR);
                if ($pivots !== []) {
                    DB::table('category_types')->insert($pivots);
                }
            }

            DB::table('types')->whereIn('id', $createdIds)
                ->whereIn('name', ['Software', 'Platform'])
                ->whereNotIn('id', DB::table('category_types')->select('type_id'))
                ->delete();

            DB::table('categories')->where('in_submission_plan', true)->update([
                'submittable' => true,
                'in_submission_plan' => false,
                'merge_target_slug' => null,
                'submit_label' => null,
                'legacy_page_type' => null,
            ]);
            DB::table('category_merge_type_backups')->delete();
        });
    }

    private function typeId($typeIds, string $name): int
    {
        if (! $typeIds->has($name)) {
            $typeIds->put($name, DB::table('types')->insertGetId([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        return $typeIds->get($name);
    }

    private function plan(): array
    {
        $file = new SplFileObject(__DIR__.'/data/category-merge-plan-50.csv');
        $columns = $file->fgetcsv(escape: '');
        $plan = [];

        while (! $file->eof()) {
            $values = $file->fgetcsv(escape: '');
            if ($values === false || $values === [null]) {
                continue;
            }

            $row = array_combine($columns, $values);
            $plan[$row['Current Slug']] = $row;
        }

        return $plan;
    }
};
