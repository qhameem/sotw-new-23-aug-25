<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomPage extends Model
{
    public const TEMPLATES = [
        'default' => 'Standard page',
        'blank' => 'Blank page — top menubar and footer',
    ];

    public const SIDEBAR_SECTIONS = [
        'launch_readiness' => 'Launch readiness card',
        'statistics' => 'Product, click, and AI readership statistics',
        'partners' => 'Partners',
        'top_categories' => 'Top categories',
        'newsletter' => 'Newsletter signup',
    ];

    public function sidebarSections(): array
    {
        return array_replace([
            'launch_readiness' => true,
            'statistics' => true,
            'partners' => false,
            'top_categories' => false,
            'newsletter' => true,
        ], array_intersect_key($this->sidebar_sections ?? [], self::SIDEBAR_SECTIONS));
    }

    protected $fillable = ['slug', 'title', 'body', 'is_published', 'sidebar_sections', 'template'];

    protected $casts = ['is_published' => 'boolean', 'sidebar_sections' => 'array'];
}
