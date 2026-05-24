<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\CustomCategorySubmission;
use App\Models\Type;
use App\Services\SlugService;
use App\Support\CategoryTypeRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillApprovedCustomCategorySubmissions extends Command
{
    protected $signature = 'products:backfill-approved-custom-categories
        {--type= : Limit to one submission type: category, use_case, best_for, or platform}
        {--dry-run : Show what would be attached or created without saving}';

    protected $description = 'Backfill approved custom category submissions that were marked approved but never attached to products.';

    public function __construct(
        private readonly SlugService $slugService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $allowedTypes = ['category', 'use_case', 'best_for', 'platform'];
        $requestedType = $this->option('type');

        if (is_string($requestedType) && $requestedType !== '' && !in_array($requestedType, $allowedTypes, true)) {
            $this->error('Invalid --type value. Use one of: ' . implode(', ', $allowedTypes));

            return self::FAILURE;
        }

        $query = CustomCategorySubmission::query()
            ->with(['product.categories.types'])
            ->where('status', 'approved')
            ->whereIn('type', $requestedType ? [$requestedType] : $allowedTypes)
            ->orderBy('id');

        $submissions = $query->get();

        if ($submissions->isEmpty()) {
            $this->warn('No matching approved custom category submissions were found.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $processed = 0;
        $attached = 0;
        $created = 0;
        $skipped = 0;

        foreach ($submissions as $submission) {
            $processed++;

            if (!$submission->product) {
                $skipped++;
                $this->warn("SKIP submission {$submission->id}: missing product.");
                continue;
            }

            $normalizedName = Str::lower(trim($submission->name));
            $category = Category::query()
                ->whereRaw('LOWER(name) = ?', [$normalizedName])
                ->first();

            $wasCreated = false;

            if (!$category) {
                $existsCheck = fn(string $slug): bool => Category::where('slug', $slug)->exists();
                $slug = $this->slugService->generateUniqueSlug($submission->name, $existsCheck);

                if ($dryRun) {
                    $created++;
                    $this->line("DRY-RUN create category '{$submission->name}' for submission {$submission->id}.");
                } else {
                    $category = Category::create([
                        'name' => $submission->name,
                        'slug' => $slug,
                        'description' => $submission->name,
                        'meta_description' => $submission->name,
                    ]);
                    $created++;
                }

                $wasCreated = true;
            }

            $typeIds = Type::query()
                ->whereIn('name', $this->typeNamesForSubmission($submission->type))
                ->pluck('id');

            $alreadyAttached = $submission->product->categories
                ->contains(fn(Category $existing) => Str::lower($existing->name) === $normalizedName);

            if ($dryRun) {
                if (!$alreadyAttached) {
                    $attached++;
                    $action = $wasCreated ? 'create+attach' : 'attach';
                    $this->info("DRY-RUN {$action} '{$submission->name}' to product {$submission->product->id}.");
                } else {
                    $skipped++;
                    $this->line("DRY-RUN skip '{$submission->name}' on product {$submission->product->id}: already attached.");
                }

                continue;
            }

            if ($category && $typeIds->isNotEmpty()) {
                $category->types()->syncWithoutDetaching($typeIds->all());
            }

            if ($category && !$alreadyAttached) {
                $submission->product->categories()->syncWithoutDetaching([$category->id]);
                $attached++;
                $this->info("ATTACHED '{$submission->name}' to product {$submission->product->id}.");
            } else {
                $skipped++;
                $this->line("SKIP '{$submission->name}' on product {$submission->product->id}: already attached.");
            }
        }

        $this->newLine();
        $this->line("Processed: {$processed}");
        $this->line("Created categories: {$created}");
        $this->line("Attached to products: {$attached}");
        $this->line("Skipped: {$skipped}");

        return self::SUCCESS;
    }

    private function typeNamesForSubmission(string $type): array
    {
        return match ($type) {
            'use_case' => CategoryTypeRegistry::namesFor(CategoryTypeRegistry::USE_CASE),
            'best_for' => CategoryTypeRegistry::namesFor(CategoryTypeRegistry::BEST_FOR),
            'platform' => CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PLATFORM),
            default => CategoryTypeRegistry::namesFor(CategoryTypeRegistry::SOFTWARE),
        };
    }
}
