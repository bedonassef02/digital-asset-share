<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\ServeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

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
    Route::post('/assets/{id}/tags/attach', [AssetController::class, 'attachTags']);
    Route::post('/assets/{id}/tags/detach', [AssetController::class, 'detachTags']);
    Route::get('/serve/{id}', ServeController::class);
});