<?php

use Illuminate\Support\Facades\Route;

// Los paneles (Filament) viven en /admin y /artista; la web pública es el frontend Next.js.
Route::get('/', fn () => redirect('/admin'));
