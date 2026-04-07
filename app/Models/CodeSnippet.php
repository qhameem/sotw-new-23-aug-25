<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CodeSnippet extends Model
{
    protected $fillable = [
        'page',
        'location',
        'code',
        'excluded_ips',
        'excluded_countries',
    ];

    protected $casts = [
        'excluded_ips' => 'array',
        'excluded_countries' => 'array',
    ];

    public function shouldRenderFor(Request $request): bool
    {
        if (! $this->matchesRequestRoute($request)) {
            return false;
        }

        return app(\App\Services\CodeSnippetVisibilityService::class)->shouldRender($this, $request);
    }

    public function matchesRequestRoute(Request $request): bool
    {
        if ($this->page === 'all') {
            return true;
        }

        return $request->routeIs(str_replace('.index', '.*', $this->page));
    }
}
