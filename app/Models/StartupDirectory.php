<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StartupDirectory extends Model
{
    public const PRICING = ['free' => 'Free', 'freemium' => 'Freemium', 'paid' => 'Paid'];
    public const LINK_TYPES = ['dofollow' => 'Dofollow', 'nofollow' => 'Nofollow', 'conditional' => 'Conditional'];
    public const CATEGORIES = [
        'launch' => 'Launch platforms',
        'discovery' => 'Software discovery',
        'reviews' => 'Software reviews',
        'ai' => 'AI directories',
        'marketplace' => 'Marketplaces',
        'community' => 'Communities',
        'general' => 'General directories',
    ];

    protected $fillable = [
        'name', 'website_url', 'submission_url', 'category', 'best_for', 'pricing',
        'price_details', 'link_type', 'dr', 'priority', 'source_url',
        'requirements', 'verified_on',
    ];

    protected $casts = ['dr' => 'integer', 'priority' => 'integer', 'verified_on' => 'date:Y-m-d'];

    public function domain(): string
    {
        return Str::replaceStart('www.', '', parse_url($this->website_url, PHP_URL_HOST) ?? '');
    }
}
