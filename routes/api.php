<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ArticleController;
use App\Http\Controllers\API\UserPreferenceController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', function () {
    return response()->json(['message' => 'POST request received']);
});

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication routes - with public API rate limiting
    Route::middleware(['throttle:api.public'])->group(function() {
        Route::prefix('user')->controller(AuthController::class)->group(function () {
            Route::post('/register', 'register');
            Route::post('/login', 'login');
            Route::post('/forgot-password', 'forgotPassword');
            Route::post('/reset-password', 'resetPassword');
        });
        
        Route::get('/user', function (Request $request) {
            return response()->json(['message' => 'GET request received']);
        });
    });
    
    // Public article routes - with articles specific rate limiting
    Route::middleware(['throttle:api.articles'])->group(function() {
        // Add response caching for public article endpoints (5 minutes cache)
        Route::get('/articles', [ArticleController::class, 'index'])->middleware('cache.headers:public;max_age=300;etag');
        Route::get('/articles/{id}', [ArticleController::class, 'show'])->middleware('cache.headers:public;max_age=300;etag');
    });
});

// Protected routes
Route::middleware(['auth:sanctum', 'throttle:api.user'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // User preferences routes
    Route::prefix('v1/user/preferences')->controller(UserPreferenceController::class)->group(function () {
        Route::get('/', 'index');  // Get user preferences
        Route::put('/', 'update'); // Update user preferences
        Route::delete('/', 'reset'); // Reset user preferences (DELETE method)
        Route::post('/reset', 'reset'); // Reset user preferences (POST method to match Swagger)
    });
    
    // Personalized feed based on user preferences - with caching (2 minutes)
    Route::get('/v1/user/feed', [UserPreferenceController::class, 'feed'])
        ->middleware('cache.headers:private;max_age=120;etag');
});

