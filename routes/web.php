<?php

use App\Http\Controllers\Operator\AdminController;
use Illuminate\Support\Facades\Route;

// Rute untuk Halaman Utama (Beranda / Landing Page)
// File view: resources/views/welcome.blade.php
Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/operator/dashboard', fn () => view('dashboards.operator'))
        ->middleware('role:operator')
        ->name('operator.dashboard');

    Route::get('/admin/dashboard', fn () => view('dashboards.admin'))
        ->middleware('role:admin,operator')
        ->name('admin.dashboard');

    Route::get('/magang/dashboard', fn () => view('dashboards.magang'))
        ->middleware('role:magang')
        ->name('magang.dashboard');

    Route::get('/karyawan/dashboard', fn () => view('dashboards.karyawan'))
        ->middleware('role:karyawan')
        ->name('karyawan.dashboard');

    Route::prefix('operator')->name('operator.')->middleware('role:operator')->group(function (): void {
        Route::resource('admins', AdminController::class)
            ->except(['show'])
            ->parameters(['admins' => 'user']);
    });
});

require __DIR__.'/auth.php';
})->name('beranda');

// Rute untuk Halaman Tentang Kami
// File view: resources/views/about.blade.php
Route::get('/tentang', function () {
    // Karena nama filenya about.blade.php, kita gunakan view('about')
    return view('about');
})->name('tentang');
