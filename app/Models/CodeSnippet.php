<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CodeSnippet extends Model
{
    protected $fillable = [
        'page',
        'pages',
        'location',
        'code',
        'excluded_ips',
        'excluded_countries',
    ];

    protected $casts = [
        'excluded_ips' => 'array',
        'excluded_countries' => 'array',
        'pages' => 'array',
    ];

    public function shouldRenderFor(Request $request): bool
    {
        if ($this->location === 'head' && ! app(\App\Support\HeaderCodeVisibility::class)->shouldRender($request)) {
            return false;
        }

        if (! $this->matchesRequestRoute($request)) {
            return false;
        }

        return app(\App\Services\CodeSnippetVisibilityService::class)->shouldRender($this, $request);
    }

    public function matchesRequestRoute(Request $request): bool
    {
        $pages = $this->targetPages();

        if (in_array('all', $pages, true)) {
            return true;
        }

        foreach ($pages as $page) {
            if ($request->routeIs(str_replace('.index', '.*', $page))) {
                return true;
            }
        }

        return false;
    }

    public function targetPages(): array
    {
        return $this->pages ?: array_filter([$this->page]);
    }
}
