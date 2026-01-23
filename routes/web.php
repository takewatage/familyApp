<?php

use App\Http\Controllers\DokController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/home');

Route::middleware(['auth'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/dok', [DokController::class, 'index'])->name('dok');
});

require __DIR__ . '/auth.php';
