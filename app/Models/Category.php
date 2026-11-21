<?php

namespace App\Models;

use App\Support\CategoryPageType;
use App\Support\CategoryTypeRegistry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;

class Category extends Model implements Sitemapable
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'meta_description',
        'submittable', 'merge_target_slug', 'submit_label', 'canonical_slug',
    ];

    protected $casts = ['submittable' => 'boolean'];

    public function scopeSubmittable(Builder $query): Builder
    {
        return $query->where('categories.submittable', true);
    }

    public static function hasSubmissionPlan(): bool
    {
        return self::where('in_submission_plan', true)->exists();
    }

    public function scopeAvailableForSubmission(Builder $query, bool $curated): Builder
    {
        return $query->submittable()->when($curated, fn (Builder $options) => $options->where('categories.in_submission_plan', true));
    }

    public static function submissionOptionRule(bool $curated): Exists
    {
        return Rule::exists('categories', 'id')->where(function ($query) use ($curated) {
            $query->where('submittable', true)->when($curated, fn ($options) => $options->where('in_submission_plan', true));
        });
    }

    public function scopeWithSoftwarePage(Builder $query, array $typeNames): Builder
    {
        return $query->where(function (Builder $category) use ($typeNames) {
            $category->whereHas('types', fn (Builder $types) => $types->whereIn('types.name', $typeNames))
                ->orWhere(function (Builder $legacy) use ($typeNames) {
                    $legacy->whereNotNull('categories.legacy_page_type')
                        ->whereExists(function ($backup) use ($typeNames) {
                            $backup->selectRaw('1')->from('category_merge_type_backups')
                                ->whereColumn('category_id', 'categories.id')
                                ->where(function ($types) use ($typeNames) {
                                    foreach ($typeNames as $name) {
                                        $types->orWhereJsonContains('original_types', $name);
                                    }
                                });
                        });
                });
        });
    }

    public static function submissionTypeIds(string $bucket, array $preservedIds = [], bool $curated = false): Collection
    {
        return static::whereHas('types', fn (Builder $types) => $types->whereIn('name', CategoryTypeRegistry::namesFor($bucket)))
            ->where(fn (Builder $options) => $options->availableForSubmission($curated)->orWhereIn('categories.id', $preservedIds))
            ->pluck('categories.id');
    }

    public function getSubmissionNameAttribute(): string
    {
        return $this->submit_label ?? $this->name;
    }

    public function pageProducts(): Builder
    {
        return Product::query()->whereHas('categories', function (Builder $query) {
            $query->where(function (Builder $categories) {
                $categories->whereKey($this->getKey());

                if ($this->merge_target_slug !== null) {
                    $categories->orWhere('categories.slug', $this->merge_target_slug);
                }
            });
        });
    }

    public function canonicalTargetUrl(): ?string
    {
        if ($this->canonical_slug === null) {
            return null;
        }

        return self::with('types')->where('slug', $this->canonical_slug)->first()?->publicUrl();
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function types()
    {
        return $this->belongsToMany(Type::class, 'category_types');
    }

    public function toSitemapTag(): Url|string|array
    {
        // Ensure the category has a slug
        if (! $this->slug) {
            return []; // Return empty array if not sitemapable
        }

        // Using the 'categories.show' route as it's used for displaying products for a category.
        $url = $this->publicUrl();

        return Url::create($url)
            ->setLastModificationDate(Carbon::parse($this->updated_at))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.7);
    }

    public function pageType(): string
    {
        return CategoryPageType::resolve($this);
    }

    public function publicUrl(array $parameters = []): string
    {
        return CategoryPageType::url($this, $parameters);
    }
}
