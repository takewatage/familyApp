<?php

use App\Http\Controllers\DokController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\FamilySettingsController;
use App\Http\Controllers\FamilySwitchController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\TaskCategoryController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\VirtualUserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/home');

Route::middleware(['auth'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/mypage', [MyPageController::class, 'index'])->name('mypage.index');
    Route::post('/mypage', [MyPageController::class, 'updateProfile'])->name('mypage.update');
    Route::get('/mypage/setting/theme-color', [MyPageController::class, 'settingsIndex'])->name('mypage.setting.theme-color.index');
    Route::post('/mypage/setting/theme-color', [MyPageController::class, 'updateSettings'])->name('mypage.setting.theme-color.update');
    Route::get('/mypage/footer-settings', [MyPageController::class, 'footerSettingsIndex'])->name('mypage.footer-settings.index');
    Route::post('/mypage/footer-settings', [MyPageController::class, 'updateFooterItems'])->name('mypage.footer-settings.update');
    Route::get('/dok', [DokController::class, 'index'])->name('dok');

    // 家族設定
    Route::get('/family/settings', [FamilySettingsController::class, 'index'])->name('family.settings.index');
    Route::patch('/family/settings', [FamilySettingsController::class, 'update'])->name('family.settings.update');
    Route::post('/family/code/regenerate', [FamilySettingsController::class, 'regenerateCode'])->name('family.code.regenerate');

    // メンバー管理
    Route::get('/family/members', [FamilyMemberController::class, 'index'])->name('family.members.index');
    Route::delete('/family/members/{user}', [FamilyMemberController::class, 'destroy'])->name('family.members.destroy');

    // 仮想ユーザー管理
    Route::post('/family/virtual-users', [VirtualUserController::class, 'store'])->name('family.virtual-users.store');
    Route::patch('/family/virtual-users/{virtualUser}', [VirtualUserController::class, 'update'])->name('family.virtual-users.update');
    Route::delete('/family/virtual-users/{virtualUser}', [VirtualUserController::class, 'destroy'])->name('family.virtual-users.destroy');

    // 家族切り替え
    Route::get('/families/switch', [FamilySwitchController::class, 'index'])->name('family.switch.index');
    Route::post('/families/{family}/switch', [FamilySwitchController::class, 'switch'])->name('family.switch');

    Route::post('/task-categories/reorder', [TaskCategoryController::class, 'reorder'])->name('task-categories.reorder');
    Route::post('/task-categories', [TaskCategoryController::class, 'store'])->name('task-categories.store');
    Route::patch('/task-categories/{taskCategory}', [TaskCategoryController::class, 'update'])->name('task-categories.update');
    Route::delete('/task-categories/{taskCategory}', [TaskCategoryController::class, 'destroy'])->name('task-categories.destroy');

    // 家計簿: 支出
    Route::get('/budget/expenses', [ExpenseController::class, 'index'])->name('budget.expenses.index');
    Route::post('/budget/expenses', [ExpenseController::class, 'store'])->name('budget.expenses.store');
    Route::patch('/budget/expenses/{expense}', [ExpenseController::class, 'update'])->name('budget.expenses.update');
    Route::delete('/budget/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('budget.expenses.destroy');

    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks');
    Route::post('/task', [TaskController::class, 'store'])->name('tasks.store');
    Route::patch('/task/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('/task/{task_id}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
    Route::delete('/task/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::delete('/tasks/completed', [TaskController::class, 'destroyCompleted'])->name('tasks.destroyCompleted');
});

require __DIR__.'/auth.php';
