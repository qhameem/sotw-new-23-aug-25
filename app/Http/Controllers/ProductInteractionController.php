<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductInteractionController extends Controller
{
    public function click(Product $product, Request $request): RedirectResponse
    {
        abort_unless($product->approved && $product->is_published && $product->link, 404);

        $product->recordOutboundClickAndAutoUpvote();

        return redirect()->away($this->appendUtmParameters(
            $product->link,
            $request->string('surface')->toString()
        ));
    }

    protected function appendUtmParameters(string $url, string $surface = ''): string
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $query = [];

        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $query['utm_source'] = 'softwareontheweb.com';

        if ($medium = $this->resolveUtmMedium($surface)) {
            $query['utm_medium'] = $medium;
        }

        $rebuilt = ($parts['scheme'] ?? 'https') . '://' . $parts['host'];

        if (isset($parts['port'])) {
            $rebuilt .= ':' . $parts['port'];
        }

        $rebuilt .= $parts['path'] ?? '';
        $rebuilt .= '?' . http_build_query($query);

        if (! empty($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }

    protected function resolveUtmMedium(string $surface): ?string
    {
        return match ($surface) {
            'product_details',
            'product_list',
            'promoted_listing_card' => $surface,
            default => null,
        };
    }
}
