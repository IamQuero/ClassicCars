<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ListingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Solo lectura por ahora: el CRUD de anuncios llegará con las políticas.
    Route::apiResource('listings', ListingController::class)->only(['index', 'show']);
});
