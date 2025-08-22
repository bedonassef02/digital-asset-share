<?php

use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AssetVersionController;
use App\Http\Controllers\Api\ServeController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\ExportController;
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
    Route::get('/assets/{asset}/export', ExportController::class);
    Route::post('/assets/{id}/tags', [TagController::class, 'add']);
    Route::delete('/assets/{id}/tags', [TagController::class, 'remove']);

    Route::get('/assets/{assetId}/versions', [AssetVersionController::class, 'index']);
    Route::get('/assets/{assetId}/versions/{version}', [AssetVersionController::class, 'show']);
    Route::post('/assets/{assetId}/versions', [AssetVersionController::class, 'store']);
    Route::delete('/assets/{assetId}/versions/{version}', [AssetVersionController::class, 'destroy']);

    Route::get('/serve/{id}', ServeController::class);

    // Search route
    Route::get('/search', [App\Http\Controllers\Api\SearchController::class, 'search']);

    // Share routes
    Route::post('/assets/{asset}/share', [App\Http\Controllers\Api\ShareController::class, 'create']);
    Route::get('/shares', [App\Http\Controllers\Api\ShareController::class, 'list']);
    Route::delete('/shares/{token}', [App\Http\Controllers\Api\ShareController::class, 'revoke']);
});

Route::get('/shares/{token}', [App\Http\Controllers\Api\ShareController::class, 'resolve']);
