<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AntrianController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rute Publik
Route::get('/', [AntrianController::class, 'kios'])->name('kios.index');
Route::post('/ambil-antrian', [AntrianController::class, 'ambil'])->name('kios.ambil');
Route::get('/display-board', [AntrianController::class, 'displayBoard'])->name('display.index');

// Rute Panel Petugas (Hanya untuk Admin/Petugas yang sudah login)
Route::middleware(['auth', 'role:admin'])->prefix('panel')->name('panel.')->group(function () {
    Route::get('/', [AntrianController::class, 'panelPetugas'])->name('index');
    Route::post('/panggil', [AntrianController::class, 'panggilBerikutnya'])->name('panggil');
    Route::post('/antrian/{id}/selesai', [AntrianController::class, 'selesai'])->name('selesai');
    Route::post('/antrian/{id}/lewati', [AntrianController::class, 'lewati'])->name('lewati');
});