<?php

use App\Http\Controllers\Api\ArtistController;
use App\Http\Controllers\Api\ArtworkController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\CommercialRequestController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UploadController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::get('/artworks', [ArtworkController::class, 'index']);
Route::get('/artworks/{id}', [ArtworkController::class, 'show']);
Route::post('/artworks/{id}/requests', [CommercialRequestController::class, 'store'])->middleware('throttle:requests');

Route::get('/artists', [ArtistController::class, 'index']);
Route::get('/artists/{slug}', [ArtistController::class, 'show']);

Route::get('/settings', [SettingController::class, 'index']);
Route::get('/site', [ContentController::class, 'site']);
Route::get('/pages/{slug}', [ContentController::class, 'page']);
// La ficha del certificado la pide sobre todo el servidor del sitio (una sola IP): tope amplio.
Route::get('/certificates/{key}', [CertificateController::class, 'show'])->middleware('throttle:300,1');
Route::middleware('throttle:verify')->group(function () {
    Route::get('/certificates/fingerprint/{sha256}', [CertificateController::class, 'byFingerprint']);
    Route::get('/certificates/{token}/pdf', [CertificateController::class, 'pdf']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateMe']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/admin/artworks', [ArtworkController::class, 'adminIndex']);
    Route::post('/artworks', [ArtworkController::class, 'store']);
    Route::put('/artworks/{id}', [ArtworkController::class, 'update']);
    Route::delete('/artworks/{id}', [ArtworkController::class, 'destroy']);
    Route::post('/uploads/image', [UploadController::class, 'image']);
    Route::post('/uploads/model', [UploadController::class, 'model']);

    Route::post('/artists', [ArtistController::class, 'store']);
    Route::put('/artists/{id}', [ArtistController::class, 'update']);
    Route::delete('/artists/{id}', [ArtistController::class, 'destroy']);

    Route::put('/settings', [SettingController::class, 'update']);
});
