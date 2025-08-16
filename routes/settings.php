<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('settings/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.store');
    Route::delete('settings/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');

    Route::get('settings/sessions', [\App\Http\Controllers\Profile\SessionsController::class, 'index'])
        ->name('settings.sessions');
});

Route::middleware(['auth.challenge:otp'])->group(function () {
    Route::get('/auth/otp',  [\App\Http\Controllers\Auth\OtpController::class, 'create'])->name('auth.otp.form');
    Route::post('/auth/otp/resend', [\App\Http\Controllers\Auth\OtpController::class, 'resend'])->name('auth.otp.resend');
});

Route::get('/auth/totp',  [\App\Http\Controllers\Auth\TotpController::class, 'create'])->middleware('auth.challenge:totp')->name('auth.totp.form');

Route::middleware(['throttle:otp'])->group(function () {
    Route::post('/auth/otp', [\App\Http\Controllers\Auth\OtpController::class, 'store'])->name('auth.otp.verify');
    Route::post('/auth/totp', [\App\Http\Controllers\Auth\TotpController::class, 'store'])->name('auth.totp.verify');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('settings/security', [\App\Http\Controllers\Profile\SecurityController::class, 'index'])
        ->name('settings.security');
    Route::get('settings/security/totp/setup', [\App\Http\Controllers\Profile\SecurityController::class, 'totpSetup'])->name('settings.security.totp.setup');
    Route::post('settings/security/totp/enable', [\App\Http\Controllers\Profile\SecurityController::class, 'totpEnable'])->name('settings.security.totp.enable');
    Route::post('settings/security/totp/disable', [\App\Http\Controllers\Profile\SecurityController::class, 'totpDisable'])->name('settings.security.totp.disable');

    Route::delete('/security/sessions/others', [\App\Http\Controllers\Profile\SessionsController::class, 'destroyOthers'])
        ->name('security.sessions.destroyOthers');
});
