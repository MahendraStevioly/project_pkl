<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordOtpController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/forgot-password', [PasswordOtpController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordOtpController::class, 'send'])
        ->name('password.otp.send')
        ->middleware('throttle:password-otp');
    Route::get('/verify-password-otp', [PasswordOtpController::class, 'showVerify'])->name('password.otp.form');
    Route::post('/verify-password-otp', [PasswordOtpController::class, 'verify'])
        ->name('password.otp.verify')
        ->middleware('throttle:10,1');
    Route::get('/reset-password', [PasswordOtpController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordOtpController::class, 'reset'])
        ->name('password.update')
        ->middleware('throttle:5,1');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
