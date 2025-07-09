<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Product extends Model implements Sitemapable
{
    protected $fillable = [
        'user_id', 'name', 'slug', 'tagline', 'description', 'link', 'votes_count', 'logo', 'product_page_tagline',
        'approved', 'published_at',
        'proposed_logo_path', 'proposed_tagline', 'proposed_description', 'has_pending_edits', // Added new fields
        'is_promoted', 'promoted_position' // Added promotion fields
    ];

    protected $casts = [
        'approved' => 'boolean',
        'published_at' => 'datetime',
        'has_pending_edits' => 'boolean', // Added cast for new field
        'is_promoted' => 'boolean',        // Added cast for promotion field
        'promoted_position' => 'integer',  // Added cast for promotion field
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
                return $this->logo;
            }

            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($this->logo)) {
                return \Illuminate\Support\Facades\Storage::url($this->logo);
            }
        }

        if ($this->link) {
            return 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($this->link);
        }

        return null;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * The categories proposed for this product when edits are pending.
     */
    public function proposedCategories()
    {
        return $this->belongsToMany(Category::class, 'product_category_proposed');
    }

    /**
     * Get the user upvotes for the product.
     */
    public function userUpvotes(): HasMany
    {
        return $this->hasMany(UserProductUpvote::class);
    }

    public function premiumSpot()
    {
        return $this->hasOne(PremiumProduct::class);
    }
/**
     * Check if the product is upvoted by the current authenticated user.
     *
     * @return bool
     */
    public function getIsUpvotedByCurrentUserAttribute(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        // Check if a UserProductUpvote record exists for this product and the current user.
        // Eager load this relationship when fetching products if performance becomes an issue.
        // For a single product check, this is fine.
        return $this->userUpvotes()->where('user_id', Auth::id())->exists();
    }

    /**
     * Get the pricing model description from associated 'Pricing' categories.
     *
     * @return string|null
     */
    public function getPricingModelDescriptionAttribute(): ?string
    {
        $pricingCategories = $this->categories->filter(function ($category) {
            // Assuming Category model has a 'types' relationship
            // And Type model has a 'name' attribute
            return $category->types->contains('name', 'Pricing');
        });

        if ($pricingCategories->isNotEmpty()) {
            return $pricingCategories->pluck('name')->implode(', ');
        }
        
        // Fallback if pricing_type field exists and has a value, and no pricing categories are found
        // This part is commented out as 'pricing_type' is not in the $fillable array currently.
        // if (!empty($this->attributes['pricing_type'])) {
        //     return $this->attributes['pricing_type'];
        // }

        return null;
    }

    public function toSitemapTag(): Url | string | array
    {
        // Ensure the product has a slug and is approved
        if (!$this->slug || !$this->approved) {
            return []; // Return empty array if not sitemapable
        }

        // Assuming you have a route named 'products.show' that takes a product slug
        // <web.php line 111 | Route::get('/{product:slug}', [ProductController::class, 'showProductPage'])->name('products.show');>
        $url = route('products.show', $this->slug);

        return Url::create($url)
            ->setLastModificationDate(Carbon::parse($this->updated_at))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority(0.8);
    }
}
