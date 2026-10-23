<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\PublicProductDocument;
use Symfony\Component\HttpFoundation\Response;

class AgentAccessController extends Controller
{
    public function index(): Response
    {
        return response(view('agent.llms')->render(), 200, $this->headers('text/plain; charset=UTF-8'));
    }

    public function product(string $slug, string $format, PublicProductDocument $document): Response
    {
        $product = Product::approvedAndPublished()
            ->where(fn ($query) => $query->where('generation_noindex', false)->orWhereNull('generation_noindex'))
            ->where('slug', $slug)->with('categories:id,name')->firstOrFail();
        $data = $document->data($product);
        $headers = $this->headers($format === 'json' ? 'application/json; charset=UTF-8' : 'text/markdown; charset=UTF-8');
        $headers['Link'] = '<'.route('products.show', $product->slug).'>; rel="canonical"';
        $headers['X-Robots-Tag'] = 'noindex, follow';

        return $format === 'json'
            ? response()->json($data, 200, $headers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : response($document->markdown($data), 200, $headers);
    }

    private function headers(string $type): array
    {
        return [
            'Content-Type' => $type,
            'Cache-Control' => 'private, no-store, no-cache, max-age=0, must-revalidate',
            'X-LiteSpeed-Cache-Control' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Vary' => 'Cookie, Authorization',
        ];
    }
}
