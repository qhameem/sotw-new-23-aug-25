# Homepage Week Selection Logic

## Issue Description
The homepage was automatically going to week 1 instead of displaying the last/most recent week with approved products. This happened because the `home()` method in `ProductController` was defaulting to the current week and only searching for the most recent week with products when the current week had no products.

## Root Cause
The original logic in the `home()` method was:

1. Get the current week
2. Check if there are products in the current week
3. Only if there are no products in the current week, find the last available week with products
4. This meant that if the current week had products, it would always display the current week, even if there were more recent weeks with products in the past

## Solution
Modified the `home()` method to always find the most recent week with approved products, regardless of whether the current week has products. The new logic:

1. Always search for the last available week with products
2. If found, use that week to display products
3. Only if no weeks with products are found, default to the current week

## Code Changes
In `app/Http/Controllers/ProductController.php`, the `home()` method was changed from:

```php
public function home(Request $request)
{
    $now = Carbon::now();
    $year = $now->year;
    $week = $now->weekOfYear;
    
    // Check if there are products for the current week (regular or promoted)
    $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek(Carbon::MONDAY);
    $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);
    
    $hasProducts = Product::where('approved', true)
        ->where('is_published', true)
        ->whereBetween(DB::raw('COALESCE(DATE(published_at), DATE(created_at))'), [
            $startOfWeek->toDateString(),
            $endOfWeek->toDateString()
        ])
        ->exists();
    
    if (!$hasProducts) {
        // Find the last available week with products
        $lastAvailableWeek = $this->findLastAvailableWeekWithProducts($startOfWeek);
        
        if ($lastAvailableWeek) {
            $year = $lastAvailableWeek->year;
            $week = $lastAvailableWeek->weekOfYear;
        }
    }
    
    return $this->productsByWeek($request, $year, $week, true);
}
```

To:

```php
public function home(Request $request)
{
    $now = Carbon::now();
    
    // Find the last available week with products (most recent week with approved products)
    $startOfWeek = Carbon::now()->setISODate($now->year, $now->weekOfYear)->startOfWeek(Carbon::MONDAY);
    $lastAvailableWeek = $this->findLastAvailableWeekWithProducts($startOfWeek);
    
    if ($lastAvailableWeek) {
        $year = $lastAvailableWeek->year;
        $week = $lastAvailableWeek->weekOfYear;
    } else {
        // If no weeks with products are found, default to current week
        $year = $now->year;
        $week = $now->weekOfYear;
    }
    
    return $this->productsByWeek($request, $year, $week, true);
}
```

## Impact
- The homepage will now always display the most recent week that has approved products
- This provides users with the latest content rather than potentially outdated current week content
- If no weeks with products exist, it will still default to the current week as a fallback

## Additional Fix
I also discovered that there was an issue in the frontend JavaScript in `resources/views/home.blade.php`. The JavaScript was defaulting to the current week for UI purposes even when the backend was correctly providing the most recent week with products. I updated the JavaScript to properly use the week information passed from the backend when displaying the homepage.

## Debugging
I've added logging to help debug the issue. The logs will show which week is being found as the most recent week with products.