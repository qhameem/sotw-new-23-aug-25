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
        'proposed_tagline',
        'proposed_description',
        'proposed_logo_path',
        'video_url',
    ];

    protected $casts = [
        'approved' => 'boolean',
        'is_promoted' => 'boolean',
        'is_published' => 'boolean',
        'has_pending_edits' => 'boolean',
        'published_at' => 'datetime',
        'video_url' => 'array',
        'maker_links' => 'array',
        'sell_product' => 'boolean',
        'asking_price' => 'decimal:2',
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

    public function toSitemapTag(): Url | string | array
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
}
