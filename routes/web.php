<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DocumentDownloadController;
use Illuminate\Support\Facades\Route;

// Los paneles (Filament) viven en /admin y /artista; la web pública es el frontend Next.js.
Route::get('/', fn () => redirect()->route('login'));

// Login único para todos los roles: cada uno sigue a su panel (o al sitio, el comprador).
Route::get('/ingresar', [LoginController::class, 'show'])->name('login');
Route::post('/ingresar', [LoginController::class, 'store']);
Route::post('/salir', [LoginController::class, 'destroy'])->name('logout');

// Archivos del expediente: la sesión es la de los paneles.
Route::get('/expediente/{version}', DocumentDownloadController::class)->name('documents.download');
