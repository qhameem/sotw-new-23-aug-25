<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;
use Carbon\Carbon;
use App\Helpers\HtmlHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Product extends Model implements Sitemapable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'product_page_tagline',
        'description',
        'link',
        'maker_links',
        'sell_product',
        'asking_price',
        'x_account',
        'logo',
        'user_id',
        'votes_count',
        'approved',
        'is_promoted',
        'promoted_position',
        'is_published',
        'published_at',
        'has_pending_edits',
        'proposed_logo_path',
        'proposed_name',
        'proposed_link',
        'proposed_video_url',
        'proposed_x_account',
        'proposed_sell_product',
        'proposed_asking_price',
        'proposed_maker_links',
        'proposed_product_page_tagline',
        'video_url',
        'last_edited_by_id',
    ];

    protected $casts = [
        'approved' => 'boolean',
        'is_promoted' => 'boolean',
        'is_published' => 'boolean',
        'has_pending_edits' => 'boolean',
        'published_at' => 'datetime',
        'maker_links' => 'array',
        'sell_product' => 'boolean',
        'asking_price' => 'decimal:2',
        'proposed_sell_product' => 'boolean',
        'proposed_asking_price' => 'decimal:2',
        'proposed_maker_links' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($product) {
            if ($product->description) {
                $product->description = HtmlHelper::addNofollowToLinks($product->description);
            }
        });
    }

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lastEditor()
    {
        return $this->belongsTo(User::class, 'last_edited_by_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function softwareCategories()
    {
        return $this->belongsToMany(Category::class)
            ->whereHas('types', function ($query) {
                $query->where('category_types.type_id', 1);
            });
    }

    public function proposedCategories()
    {
        return $this->belongsToMany(Category::class, 'product_category_proposed');
    }

    public function proposedTechStacks()
    {
        return $this->belongsToMany(TechStack::class, 'product_tech_stack_proposed');
    }

    public function userUpvotes()
    {
        return $this->hasMany(UserProductUpvote::class);
    }

    public function premiumSpot()
    {
        return $this->hasOne(PremiumProduct::class);
    }

    public function media()
    {
        return $this->hasMany(ProductMedia::class);
    }

    public function techStacks()
    {
        return $this->belongsToMany(TechStack::class, 'product_tech_stack');
    }

    public function getIsUpvotedByCurrentUserAttribute()
    {
        if (!Auth::check()) {
            return false;
        }
        return $this->userUpvotes()->where('user_id', Auth::id())->exists();
    }

    public function getEmbedUrl()
    {
        if (!$this->video_url) {
            return null;
        }

        if (str_contains($this->video_url, 'youtube.com') || str_contains($this->video_url, 'youtu.be')) {
            $videoId = '';
            if (preg_match('/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $this->video_url, $matches)) {
                $videoId = $matches[1];
            }
            return 'https://www.youtube.com/embed/' . $videoId;
        }

        if (str_contains($this->video_url, 'vimeo.com')) {
            $videoId = '';
            if (preg_match('/(?:https?:\/\/)?(?:www\.)?vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|)(\d+)/', $this->video_url, $matches)) {
                $videoId = $matches[3];
            }
            return 'https://player.vimeo.com/video/' . $videoId;
        }

        return null;
    }

    public function getVideoId()
    {
        if (!$this->video_url) {
            return null;
        }

        if (str_contains($this->video_url, 'youtube.com') || str_contains($this->video_url, 'youtu.be')) {
            if (preg_match('/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $this->video_url, $matches)) {
                return $matches[1];
            }
        }

        if (str_contains($this->video_url, 'vimeo.com')) {
            if (preg_match('/(?:https?:\/\/)?(?:www\.)?vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|)(\d+)/', $this->video_url, $matches)) {
                return $matches[3];
            }
        }

        return null;
    }

    public function getPricingModelDescriptionAttribute(): ?string
    {
        $pricingCategories = $this->categories->filter(function ($category) {
            return $category->types->contains('name', 'Pricing');
        });

        if ($pricingCategories->isNotEmpty()) {
            return $pricingCategories->pluck('name')->implode(', ');
        }

        return null;
    }

    public function toSitemapTag(): Url|string|array
    {
        if (!$this->slug || !$this->approved) {
            return [];
        }

        $url = route('products.show', $this->slug);

        return Url::create($url)
            ->setLastModificationDate(Carbon::parse($this->updated_at))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority(0.8);
    }

    /**
     * Scope to get only approved and published products
     */
    public function scopeApprovedAndPublished(Builder $query): Builder
    {
        return $query->where('approved', true)
            ->where('is_published', true);
    }

    /**
     * Scope to get products by week
     */
    public function scopeByWeek(Builder $query, Carbon $startOfWeek, Carbon $endOfWeek): Builder
    {
        return $query->whereBetween(DB::raw('COALESCE(published_at, created_at)'), [
            $startOfWeek->toDateString(),
            $endOfWeek->toDateString()
        ]);
    }

    /**
     * Scope to get promoted products only
     */
    public function scopePromoted(Builder $query): Builder
    {
        return $query->where('is_promoted', true)
            ->whereNotNull('promoted_position')
            ->orderBy('promoted_position', 'asc');
    }

    /**
     * Scope to get non-promoted products
     */
    public function scopeNonPromoted(Builder $query): Builder
    {
        return $query->where('is_promoted', false);
    }
}
