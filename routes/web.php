<?php

use App\Http\Controllers\DocumentDownloadController;
use Illuminate\Support\Facades\Route;

// Los paneles (Filament) viven en /admin y /artista; la web pública es el frontend Next.js.
Route::get('/', fn () => redirect('/admin'));

// Archivos del expediente: la sesión es la de los paneles.
Route::get('/expediente/{version}', DocumentDownloadController::class)->name('documents.download');
