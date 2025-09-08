<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\ArticlePostController; // Added
use App\Http\Controllers\Admin\ArticleCategoryController; // Added
use App\Http\Controllers\Admin\ArticleTagController; // Added
use App\Http\Controllers\Admin\AdZoneController; // Added for Ad Zones
use App\Http\Controllers\Admin\AdController; // Added for Ads
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ChangelogController as AdminChangelogController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\ArticleController; // Added for public articles
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TopicController; // Added for topics page
use App\Http\Controllers\Api\ProductMetaController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\StripeController;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\TodoListController;

Route::resource('product-reviews', ProductReviewController::class)->only(['create', 'store']);

Route::post('/set-intended-url', [RedirectController::class, 'setIntendedUrl'])->name('set-intended-url');

Route::get('/', [ProductController::class, 'home'])->name('home');

Route::get('/thank-you', function () {
    return view('subscription.thankyou');
})->name('subscription.thankyou');


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/add-product', [ProductController::class, 'create'])->name('products.create');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/notifications', [ProfileController::class, 'updateNotificationPreferences'])->name('profile.update.notifications'); // Added route
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/my-products', [ProductController::class, 'myProducts'])->name('products.my');
    Route::get('/products/submission-success/{product}', [ProductController::class, 'showSubmissionSuccess'])->name('products.submission.success');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::get('/subscribe', [SubscriptionController::class, 'create'])->name('subscribe');
    Route::post('/stripe/checkout', [StripeController::class, 'checkout'])->name('stripe.checkout');

    // Article routes for authenticated users
    Route::get('/articles/create', [ArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [ArticleController::class, 'store'])->name('articles.store');
    Route::get('/my-articles', [ArticleController::class, 'myArticles'])->name('articles.my');
    Route::get('/promote/success', [StripeController::class, 'promoteSuccess'])->name('promote.success');
    Route::post('/promote/update-date', [StripeController::class, 'updateDate'])->name('promote.update-date');
});

Route::get('/stripe/cancel', [StripeController::class, 'cancel'])->name('stripe.cancel');
Route::post('/stripe/webhook', [StripeController::class, 'webhook'])->name('stripe.webhook');
Route::post('/stripe/product-review-checkout', [StripeController::class, 'productReviewCheckout'])->name('stripe.product-review.checkout');
Route::get('/stripe/product-review/success', [StripeController::class, 'productReviewSuccess'])->name('stripe.product-review.success');

// TEMPORARY TEST ROUTE - REMOVE ADMIN GROUPING
Route::any('/temporary-bulk-delete-test-no-name', [\App\Http\Controllers\Admin\ProductController::class, 'bulkDelete'])->middleware('auth'); // Changed to Route::any for diagnostics

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('categories', CategoryController::class);
    // Define specific product routes BEFORE the resource controller for products
    // Test route for debugging 404
    Route::get('products/test-pending-edits', function () { return 'Test route for pending edits is working.'; })->name('products.test-pending-edits');
    // Routes for managing edits to approved products
    Route::get('products/pending-edits', [\App\Http\Controllers\Admin\ProductApprovalController::class, 'pendingEditsIndex'])->name('products.pending-edits.index');
    Route::get('products/{product}/review-edits', [\App\Http\Controllers\Admin\ProductApprovalController::class, 'showEditDiff'])->name('products.review-edits');
    Route::post('products/{product}/approve-edits', [\App\Http\Controllers\Admin\ProductApprovalController::class, 'approveEdits'])->name('products.approve-edits');
    Route::post('products/{product}/reject-edits', [\App\Http\Controllers\Admin\ProductApprovalController::class, 'rejectEdits'])->name('products.reject-edits');

    Route::resource('products', \App\Http\Controllers\Admin\ProductController::class); // Product resource routes last
Route::post('products/{product}/update-promotion', [\App\Http\Controllers\Admin\ProductController::class, 'updatePromotion'])->name('products.updatePromotion');
    Route::resource('category-types', \App\Http\Controllers\Admin\CategoryTypeController::class);
    Route::resource('ad-zones', AdZoneController::class); // Ad Zone CRUD
    Route::resource('ads', AdController::class); // Ad CRUD
    Route::get('product-approvals', [\App\Http\Controllers\Admin\ProductApprovalController::class, 'index'])->name('product-approvals.index');
    Route::post('product-approvals/{product}/approve', [\App\Http\Controllers\Admin\ProductApprovalController::class, 'approve'])->name('product-approvals.approve');
    Route::post('product-approvals/{product}/disapprove', [\App\Http\Controllers\Admin\ProductApprovalController::class, 'disapprove'])->name('product-approvals.disapprove');
    Route::post('product-approvals/bulk-approve', [\App\Http\Controllers\Admin\ProductApprovalController::class, 'bulkApprove'])->name('product-approvals.bulk-approve');
    // Route::post('products/bulk-delete', [\App\Http\Controllers\Admin\ProductController::class, 'bulkDelete'])->name('products.bulk-delete'); // Commented out original

    // Theme settings
    // Temporarily remove 'role:admin' for testing if it affects /api/user redirect
    // Route::get('theme', [ThemeController::class, 'edit'])->name('theme.edit')->middleware(['auth']); // If you want to test it directly
    Route::get('theme', [ThemeController::class, 'edit'])->name('theme.edit'); // Test without role middleware
    Route::put('theme', [ThemeController::class, 'update'])->name('theme.update');

    // Articles Management Routes
    Route::prefix('articles')->name('articles.')->group(function () {
        // Admin ArticlePost routes using ID for binding
        Route::get('posts', [ArticlePostController::class, 'index'])->name('posts.index');
        Route::get('posts/create', [ArticlePostController::class, 'create'])->name('posts.create');
        Route::get('posts/{post:id}/edit', [ArticlePostController::class, 'edit'])->name('posts.edit'); // Explicitly bind by ID
        Route::put('posts/{post:id}', [ArticlePostController::class, 'update'])->name('posts.update');   // Explicitly bind by ID
        Route::delete('posts/{post:id}', [ArticlePostController::class, 'destroy'])->name('posts.destroy'); // Explicitly bind by ID
        Route::put('posts/{post:id}/toggle-staff-pick', [ArticlePostController::class, 'toggleStaffPick'])->name('posts.toggleStaffPick');
        // Admin show route, if needed, would also use ID
        // Route::get('posts/{post:id}', [ArticlePostController::class, 'show'])->name('posts.show');


        Route::resource('categories', ArticleCategoryController::class)->except(['show']);
        Route::resource('tags', ArticleTagController::class)->except(['show']);
        Route::post('posts/upload-featured-image', [ArticlePostController::class, 'uploadFeaturedImage'])->name('posts.uploadFeaturedImage');
    }); // End of articles prefix group

    // Admin Settings Routes (should be direct children of admin group)
    Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings/export-database', [\App\Http\Controllers\Admin\SettingsController::class, 'exportDatabase'])->name('settings.exportDatabase');
    Route::post('settings/store-analytics', [\App\Http\Controllers\Admin\SettingsController::class, 'storeAnalyticsCode'])->name('settings.storeAnalyticsCode');
    Route::post('settings/send-test-email', [\App\Http\Controllers\Admin\SettingsController::class, 'sendTestEmail'])->name('settings.sendTestEmail');
    Route::post('settings/store-premium-product-spots', [\App\Http\Controllers\Admin\SettingsController::class, 'storePremiumProductSpots'])->name('settings.storePremiumProductSpots');
    Route::post('settings/store-publish-time', [\App\Http\Controllers\Admin\SettingsController::class, 'storePublishTime'])->name('settings.storePublishTime');
    Route::get('settings/email-templates', [\App\Http\Controllers\Admin\SettingsController::class, 'emailTemplates'])->name('settings.emailTemplates');
    Route::post('settings/email-templates', [\App\Http\Controllers\Admin\SettingsController::class, 'storeEmailTemplates'])->name('settings.storeEmailTemplates');

    // SEO Meta Tag Management
    Route::get('seo', function () {
        return view('admin.seo.index');
    })->name('seo.index');
    Route::resource('users', UserController::class)->only(['index', 'show']);
    Route::get('premium-products', [\App\Http\Controllers\Admin\PremiumProductController::class, 'index'])->name('premium-products.index');
    Route::get('product-reviews', [ProductReviewController::class, 'index'])->name('product-reviews.index');
    Route::patch('product-reviews/{product_review}', [ProductReviewController::class, 'update'])->name('product-reviews.update');
    Route::delete('premium-products/{premium_product}', [\App\Http\Controllers\Admin\PremiumProductController::class, 'destroy'])->name('premium-products.destroy');
    Route::resource('changelogs', AdminChangelogController::class)->except(['show']);
}); // End of admin prefix group

Route::get('/api/product-meta', ProductMetaController::class);
Route::get('/check-product-url', [ProductController::class, 'checkUrl']);
Route::get('/fetch-url-data', [ProductController::class, 'fetchUrlData'])->name('fetch-url-data');

// Topics page to display all categories
Route::get('/topics', [TopicController::class, 'index'])->name('topics.index');
// Individual Topic/Category page
Route::get('/topics/{category:slug}', [TopicController::class, 'show'])->name('topics.category');
// Categories page (alias for topics)
Route::get('/categories', [TopicController::class, 'index'])->name('categories.index');
Route::get('/category/{category:slug}', [ProductController::class, 'categoryProducts'])->name('categories.show');
Route::get('/products/dates', [ProductController::class, 'getProductDates']);
Route::get('/date/{date}', [ProductController::class, 'productsByDate'])->where('date', '\d{4}-\d{2}-\d{2}')->name('products.byDate');

Route::get('/weekly', [ProductController::class, 'redirectToCurrentWeek'])->name('products.weekly.redirect');
Route::get('/weekly/{year}/{week}', [ProductController::class, 'productsByWeek'])->name('products.byWeek');

Route::get('/monthly', [ProductController::class, 'redirectToCurrentMonth'])->name('products.monthly.redirect');
Route::get('/monthly/{year}/{month}', [ProductController::class, 'productsByMonth'])->name('products.byMonth');

Route::get('/yearly', [ProductController::class, 'redirectToCurrentYear'])->name('products.yearly.redirect');
Route::get('/yearly/{year}', [ProductController::class, 'productsByYear'])->name('products.byYear');
Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');



Route::get('/test-notification', function () {
    if (Auth::check()) {
        /** @var User $user */
        $user = Auth::user();
        // Send a sample UserMentioned notification
        $user->notify(new \App\Notifications\UserMentioned("This is a test notification from the test route!", "/profile"));
        return "Test notification sent to " . $user->email . ". Check your notification bell!";
    }
    return "You need to be logged in to test notifications.";
})->middleware('auth')->name('test.notification');

// Public Articles Routes
Route::prefix('articles')->name('articles.')->group(function () {
    Route::get('/', [ArticleController::class, 'index'])->name('index');
    Route::get('/search', [ArticleController::class, 'search'])->name('search');
    Route::get('/feed', [ArticleController::class, 'feed'])->name('feed'); // Will be handled by Spatie Feed package
    Route::get('/category/{articleCategory:slug}', [ArticleController::class, 'category'])->name('category');
    Route::get('/tag/{articleTag:slug}', [ArticleController::class, 'tag'])->name('tag');
    Route::get('/{article:slug}', [ArticleController::class, 'show'])->name('show'); // Must be last to avoid conflict
});

use App\Http\Controllers\Auth\GoogleLoginController; // We will create this controller next

// Google OAuth routes
Route::get('/auth/google', [GoogleLoginController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleLoginController::class, 'handleGoogleCallback']);
require __DIR__ . '/auth.php';

// About Page
Route::get('/about', function () {
    return view('site.about');
})->name('about');
// Legal Page
Route::get('/legal', function () {
    return view('site.legal');
})->name('legal');
// Promote Your Software Page

// FAQ Page
Route::get('/faq', function () {
    return view('site.faq');
})->name('faq');

// Dedicated product page - Ensure this doesn't conflict with article post slugs if products can have any slug.
// If product slugs might overlap with 'articles', consider prefixing product routes, e.g., /products/{product:slug}
// Redirect for old product URLs
Route::get('/{product_name}', function ($product_name) {
    // Check if a product with this slug exists
    $product = \App\Models\Product::where('slug', $product_name)->first();

    if ($product) {
        return redirect()->route('products.show', ['product' => $product->slug], 301);
    }

    // If no product is found, it might be a request for a non-existent page,
    // so we let Laravel handle it (which will likely result in a 404).
    abort(404);
})->where('product_name', '^(?!admin|api|auth|images|storage|css|js|articles|topics|category|date|weekly|monthly|yearly|my-products|add-product|subscribe|promote|fast-track|premium-spot|product-reviews|about|legal|faq|dashboard|profile|login|register|password|email|logout|home|set-intended-url|thank-you|stripe|temporary-bulk-delete-test-no-name|check-product-url|test-notification|promote-your-software|software-review|premium-spot-details|changelog|free-todo-list-tool)[^/]+$');

Route::get('/product/{product:slug}', [ProductController::class, 'showProductPage'])->name('products.show');







use App\Http\Controllers\SiteController;

use App\Http\Controllers\FastTrackController;
use App\Http\Controllers\PremiumSpotController;

Route::get('/promote-your-software', [SiteController::class, 'promote'])->name('promote');

Route::get('/fast-track', [FastTrackController::class, 'index'])->name('fast-track.index');

Route::get('/fast-track-approval', function () {
    return view('site.fast-track-approval');
})->name('fast-track-approval');

Route::get('/premium-spot', [PremiumSpotController::class, 'index'])->name('premium-spot.index');
Route::post('/premium-spot/checkout', [PremiumSpotController::class, 'checkout'])->name('premium-spot.checkout');
Route::get('/premium-spot/success', [PremiumSpotController::class, 'success'])->name('premium-spot.success');


Route::get('/software-review', function () {
    return view('site.software-review');
})->name('software-review');

Route::get('/changelog', [App\Http\Controllers\ChangelogController::class, 'index'])->name('changelog.index');

Route::get('/premium-spot-details', [\App\Http\Controllers\PremiumSpotController::class, 'details'])->name('premium-spot.details');

Route::middleware('auth')->group(function () {
    Route::get('/my-articles', [App\Http\Controllers\ArticleController::class, 'myArticles'])->name('articles.my');
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('email-logs', [\App\Http\Controllers\Admin\EmailLogController::class, 'index'])->name('email-logs.index');
});

Route::prefix('free-todo-list-tool')->name('todolists.')->group(function () {
    Route::get('/', [TodoListController::class, 'index'])->name('index');
    Route::post('/', [TodoListController::class, 'store'])->name('store');
    Route::put('/{todoList}', [TodoListController::class, 'update'])->name('update');
    Route::delete('/{todoList}', [TodoListController::class, 'destroy'])->name('destroy');
    Route::get('/{todoList}/export', [TodoListController::class, 'export'])->name('export');

    Route::post('/{todoList}/items', [TodoListController::class, 'storeItem'])->name('items.store');
    Route::put('/items/{todoListItem}', [TodoListController::class, 'updateItem'])->name('items.update');
    Route::delete('/items/{todoListItem}', [TodoListController::class, 'destroyItem'])->name('items.destroy');
});

