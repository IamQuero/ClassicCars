<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\PhotoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::apiResource('listings', ListingController::class)->only(['index', 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        Route::apiResource('listings', ListingController::class)->only(['store', 'update', 'destroy']);

        Route::post('listings/{listing}/photos', [PhotoController::class, 'store']);
        Route::put('listings/{listing}/photos/order', [PhotoController::class, 'reorder']);
        Route::delete('listings/{listing}/photos/{photo}', [PhotoController::class, 'destroy']);
    });
});
