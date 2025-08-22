<?php

use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AssetVersionController;
use App\Http\Controllers\Api\ServeController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test', function (Request $request) {
    return response()->json(['message' => 'API is working']);
});

// Authentication routes
Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/user', [App\Http\Controllers\Api\AuthController::class, 'user']);

    Route::apiResource('/assets', AssetController::class);
    Route::post('/assets/{id}/tags', [TagController::class, 'add']);
    Route::delete('/assets/{id}/tags', [TagController::class, 'remove']);

    Route::get('/assets/{assetId}/versions', [AssetVersionController::class, 'index']);
    Route::get('/assets/{assetId}/versions/{version}', [AssetVersionController::class, 'show']);
    Route::post('/assets/{assetId}/versions', [AssetVersionController::class, 'store']);
    Route::delete('/assets/{assetId}/versions/{version}', [AssetVersionController::class, 'destroy']);

    Route::get('/serve/{id}', ServeController::class);
});
