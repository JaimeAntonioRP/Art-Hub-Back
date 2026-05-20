<?php

use App\Http\Controllers\Api\ArtworkController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\ValidationController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/artworks', [ArtworkController::class, 'index']);
Route::get('/artworks/{id}', [ArtworkController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/admin/artworks', [ArtworkController::class, 'adminIndex']);
    Route::post('/artworks', [ArtworkController::class, 'store']);
    Route::put('/artworks/{id}', [ArtworkController::class, 'update']);
    Route::delete('/artworks/{id}', [ArtworkController::class, 'destroy']);
    Route::post('/uploads/image', [UploadController::class, 'image']);

    Route::post('/verify', [ValidationController::class, 'verify']);

    Route::post('/purchase', [TransactionController::class, 'purchase']);
});
