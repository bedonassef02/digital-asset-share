<?php

use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AssetVersionController;
use App\Http\Controllers\Api\DownloadController;
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

    // Asset Routes
    Route::apiResource('/assets', AssetController::class);
    Route::prefix('assets')->group(function () {
        Route::delete('/', [AssetController::class, 'bulkDestroy']);
        Route::post('/tags', [AssetController::class, 'bulkTag']);
        Route::post('/{id}/status', [AssetController::class, 'changeStatus']);
        Route::post('/{id}/restore', [AssetController::class, 'restore']);

        // Nested Asset Tags
        Route::post('/{id}/tags', [TagController::class, 'add']);
        Route::delete('/{id}/tags', [TagController::class, 'remove']);
    });

    // Nested Asset Versions
    Route::apiResource('assets.versions', AssetVersionController::class);

    // Download Route
    Route::post('/assets/bulk-download', [DownloadController::class, 'bulkDownload']);

    // Serve Route
    Route::get('/serve/{id}', ServeController::class);

    // Search Route
    Route::get('/search', [App\Http\Controllers\Api\SearchController::class, 'search']);

    // Share Routes
    Route::post('/assets/{asset}/share', [App\Http\Controllers\Api\ShareController::class, 'shareAsset']);
    Route::post('/collections/{collection}/share', [App\Http\Controllers\Api\ShareController::class, 'shareCollection']);
    Route::get('/shares', [App\Http\Controllers\Api\ShareController::class, 'list']);
    Route::get('/shares/{token}', [App\Http\Controllers\Api\ShareController::class, 'resolve']);
    Route::delete('/shares/{token}', [App\Http\Controllers\Api\ShareController::class, 'revoke']);
    Route::get('/shares/{token}/stats', [App\Http\Controllers\Api\ShareController::class, 'stats']);

    // Collection Routes
    Route::apiResource('/collections', App\Http\Controllers\Api\CollectionController::class);
    Route::prefix('collections')->group(function () {
        Route::post('/{id}/assets', [App\Http\Controllers\Api\CollectionController::class, 'addAssets']);
        Route::delete('/{id}/assets', [App\Http\Controllers\Api\CollectionController::class, 'removeAssets']);
        Route::get('/{id}/assets', [App\Http\Controllers\Api\CollectionController::class, 'getCollectionAssets']);
        Route::get('/root', [App\Http\Controllers\Api\CollectionController::class, 'getRootCollections']);
        Route::get('/{parentId}/children', [App\Http\Controllers\Api\CollectionController::class, 'getChildCollections']);
    });
});
