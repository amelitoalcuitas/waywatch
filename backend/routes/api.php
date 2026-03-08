<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ImageUploadController;
use App\Http\Controllers\Api\MarkerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/markers', [MarkerController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/markers', [MarkerController::class, 'store']);
        Route::post('/markers/images/upload', [ImageUploadController::class, 'upload']);
        Route::post('/markers/{marker}/vote', [MarkerController::class, 'vote']);
        Route::post('/markers/{marker}/report', [MarkerController::class, 'report']);
        Route::delete('/markers/{marker}', [MarkerController::class, 'destroy']);
    });
});
