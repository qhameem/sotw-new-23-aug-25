<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\UpvoteController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\SearchController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    \Illuminate\Support\Facades\Log::info('Accessing /api/user route.');
    $user = $request->user();
    \Illuminate\Support\Facades\Log::info('User from /api/user:', ['user' => $user ? $user->toArray() : null]);
    return $user;
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('api.notifications.markAllAsRead');
    Route::put('/notifications/{notification_id}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.markAsRead');

    // Product Upvote Routes
    Route::post('/products/{product:slug}/upvote', [UpvoteController::class, 'store'])->name('api.products.upvote.store');
    Route::delete('/products/{product:slug}/upvote', [UpvoteController::class, 'destroy'])->name('api.products.upvote.destroy');
});

Route::get('/ga-stats', [AnalyticsController::class, 'getStats'])->name('api.ga-stats');
Route::get('/analytics/total-sessions', [AnalyticsController::class, 'getTotalSessions']);
Route::get('/search', [SearchController::class, 'search'])->name('api.search');