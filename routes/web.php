<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn() => inertia('Dashboard'))->name('dashboard');
});



require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
