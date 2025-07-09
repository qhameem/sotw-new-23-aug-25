<?php

namespace App\View\Components;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use App\Models\Product; // Assuming you might want to get product names
use App\Models\Category; // Assuming you might want to get category names

class Breadcrumbs extends Component
{
    public array $items = [];

    public function __construct(Request $request)
    {
        $this->items = $this->generateBreadcrumbs($request);
    }

    private function generateBreadcrumbs(Request $request): array
    {
        $breadcrumbs = [];
        $path = $request->path();

        // Always add Home
        $breadcrumbs[] = ['label' => 'Home', 'url' => route('home')];

        // If it's the homepage, no further breadcrumbs are needed.
        // The component rendering is already skipped on the homepage via app.blade.php.
        if ($path === '/' || $path === '') {
            // $breadcrumbs[0]['url'] = null; // Mark Home as current if it's the only item.
            return $breadcrumbs; // Only "Home"
        }

        $segments = explode('/', trim($path, '/'));
        $currentPath = '';

        foreach ($segments as $index => $segment) {
            if (empty($segment)) {
                continue;
            }

            $currentPath .= '/' . $segment;
            $isLast = $index === count($segments) - 1;
            
            $label = $this->getLabelForSegment($segment, $segments, $index, $isLast, $request); // Pass request

            // Attempt to fetch Category name
            if ($index > 0 && $segments[$index-1] === 'topics' && !is_numeric($segment)) {
                $category = Category::where('slug', $segment)->first();
                if ($category) {
                    $label = $category->name;
                }
            }
            // Attempt to fetch Product name by slug (general case)
            elseif ($index > 0 && in_array($segments[$index-1], ['products', 'tool']) && !is_numeric($segment) && !in_array($segment, ['create', 'edit', 'submission-success'])) {
                $product = Product::where('slug', $segment)->first();
                if ($product) {
                    $label = $product->name;
                }
            }
            // Specifically for /products/submission-success/{product_id}
            elseif ($isLast && $segment !== 'submission-success' && $index > 1 && $segments[$index-2] === 'products' && $segments[$index-1] === 'submission-success') {
                $product = Product::find($segment); // Find by ID
                if ($product) {
                    $label = $product->name;
                } else {
                    $label = "Product " . $segment; // Fallback if not found
                }
            }

            $url = $isLast ? null : url($currentPath);

            // Special URL for "Submission Success" segment
            if ($segment === 'submission-success' && $index > 0 && $segments[$index-1] === 'products') {
                if ($request->user() && $request->user()->hasRole('admin')) {
                    $url = route('admin.products.index');
                } else {
                    $url = route('products.my');
                }
            }

            $breadcrumbs[] = [
                'label' => $label,
                'url' => $url,
            ];
        }

        return $breadcrumbs;
    }

    private function getLabelForSegment(string $segment, array $allSegments, int $currentIndex, bool $isLast, Request $request): string
    {
        // Specific labels for known segments
        if (strtolower($segment) === 'submission-success' && $currentIndex > 0 && $allSegments[$currentIndex-1] === 'products') {
            return 'Submission Success';
        }
        if (strtolower($segment) === 'topics' && $currentIndex === 0) {
            return 'Topics';
        }
        if (strtolower($segment) === 'admin' && $currentIndex === 0) {
            return 'Admin Panel';
        }
        if (strtolower($segment) === 'my-products') {
            return 'My Products';
        }
        if (strtolower($segment) === 'product-approvals' && $currentIndex > 0 && $allSegments[$currentIndex-1] === 'admin') {
            return 'Product Approvals';
        }
         if (strtolower($segment) === 'category-types' && $currentIndex > 0 && $allSegments[$currentIndex-1] === 'admin') {
            return 'Category Types';
        }
        if (strtolower($segment) === 'theme' && $currentIndex > 0 && $allSegments[$currentIndex-1] === 'admin') {
            return 'Theme Settings';
        }
        // For segments like /admin/categories or /admin/products when they are not the last segment
        if (in_array(strtolower($segment), ['categories', 'products']) && $currentIndex > 0 && $allSegments[$currentIndex-1] === 'admin' && !$isLast) {
             return Str::title($segment);
        }


        // Basic transformation: replace hyphens/underscores with spaces and capitalize
        $label = Str::title(str_replace(['-', '_'], ' ', $segment));

        // Specific overrides for action segments like 'edit' or 'create'
        if (in_array(strtolower($segment), ['edit', 'create'])) {
            return Str::title($segment); // Ensures "Edit" or "Create"
        }
        
        // If it's a date segment like /date/YYYY-MM-DD
        if ($currentIndex > 0 && $allSegments[$currentIndex-1] === 'date') {
            try {
                return 'Products on ' . \Carbon\Carbon::parse($segment)->format('M d, Y');
            } catch (\Exception $e) {
                // Fallback to default label if parsing fails
            }
        }

        return $label;
    }

    public function render()
    {
        // The component will not render if on the homepage, handled in app.blade.php
        return view('components.breadcrumbs');
    }
}