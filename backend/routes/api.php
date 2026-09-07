<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CarController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\MyListingController;
use App\Http\Controllers\Api\V1\PhotoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('throttle:auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::apiResource('listings', ListingController::class)->only(['index', 'show']);
    Route::get('cars/{car}/price-history', [CarController::class, 'priceHistory']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        Route::apiResource('listings', ListingController::class)->only(['store', 'update', 'destroy']);

        Route::post('listings/{listing}/photos', [PhotoController::class, 'store']);
        Route::put('listings/{listing}/photos/order', [PhotoController::class, 'reorder']);
        Route::delete('listings/{listing}/photos/{photo}', [PhotoController::class, 'destroy']);

        Route::get('me/listings', [MyListingController::class, 'index']);
        Route::get('me/favorites', [FavoriteController::class, 'index']);
        Route::post('listings/{listing}/favorite', [FavoriteController::class, 'store']);
        Route::delete('listings/{listing}/favorite', [FavoriteController::class, 'destroy']);

        Route::post('listings/{listing}/messages', [MessageController::class, 'store']);
        Route::get('me/conversations', [MessageController::class, 'conversations']);
        Route::get('me/conversations/{listing}/{user}', [MessageController::class, 'thread']);
    });
});
