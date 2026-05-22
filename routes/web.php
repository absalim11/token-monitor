<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KeyController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // API Routes
    Route::prefix('api')->name('api.')->group(function () {
        // Dashboard API
        Route::get('/keys', [DashboardController::class, 'keys'])->name('keys');
        Route::get('/daily-spend', [DashboardController::class, 'dailySpend'])->name('daily-spend');
        Route::get('/user-activity', [DashboardController::class, 'userActivity'])->name('user-activity');
        Route::get('/health', [DashboardController::class, 'health'])->name('health');
        Route::get('/models', [DashboardController::class, 'models'])->name('models');

        // Key Management API
        Route::prefix('keys')->name('keys.')->group(function () {
            Route::post('/generate', [KeyController::class, 'generate'])->name('generate');
            Route::post('/delete', [KeyController::class, 'delete'])->name('delete');
            Route::post('/block', [KeyController::class, 'block'])->name('block');
            Route::post('/unblock', [KeyController::class, 'unblock'])->name('unblock');
            Route::post('/update', [KeyController::class, 'update'])->name('update');
            Route::get('/info', [KeyController::class, 'info'])->name('info');
        });
    });

    // Profile routes (keep existing)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
