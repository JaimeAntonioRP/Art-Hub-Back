<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ObrasController;
use App\Http\Controllers\Admin\ArtistasController;
use App\Http\Controllers\Admin\CertificadosController;
use App\Http\Controllers\Admin\UsuariosController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('admin.login'));

// ── Admin auth (public) ──────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});

// ── Admin panel (requires auth + admin role) ─────────────────────
Route::prefix('admin')->name('admin.')->middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('obras',        ObrasController::class)->parameters(['obras' => 'obra']);
    Route::resource('artistas',     ArtistasController::class)->parameters(['artistas' => 'artista']);

    Route::get('certificados',                  [CertificadosController::class, 'index'])->name('certificados.index');
    Route::patch('certificados/{certificado}/toggle', [CertificadosController::class, 'toggle'])->name('certificados.toggle');

    Route::get('usuarios',                      [UsuariosController::class, 'index'])->name('usuarios.index');
    Route::patch('usuarios/{usuario}/role',     [UsuariosController::class, 'updateRole'])->name('usuarios.role');
});
