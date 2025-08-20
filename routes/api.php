<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\ServeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test', function (Request $request) {
    return response()->json(['message' => 'API is working']);
});

Route::apiResource('/assets', AssetController::class);

Route::get('/serve', ServeController::class);