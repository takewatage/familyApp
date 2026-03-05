<?php

use App\Http\Controllers\DokController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TaskCategoryController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/home');

Route::middleware(['auth'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/mypage', [MyPageController::class, 'index'])->name('mypage');
    Route::get('/dok', [DokController::class, 'index'])->name('dok');

    Route::post('/task-categories/reorder', [TaskCategoryController::class, 'reorder'])->name('task-categories.reorder');
    Route::post('/task-categories', [TaskCategoryController::class, 'store'])->name('task-categories.store');
    Route::patch('/task-categories/{taskCategory}', [TaskCategoryController::class, 'update'])->name('task-categories.update');
    Route::delete('/task-categories/{taskCategory}', [TaskCategoryController::class, 'destroy'])->name('task-categories.destroy');

    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks');
    Route::post('/task', [TaskController::class, 'store'])->name('tasks.store');
    Route::patch('/task/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('/task/{task_id}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
    Route::delete('/task/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
});

require __DIR__ . '/auth.php';
