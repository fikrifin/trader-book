<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyJournalController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InstrumentController;
use App\Http\Controllers\MonthlyTargetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\StatisticController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\TradingAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);

    Route::get('/trades/export', [ExportController::class, 'trades'])->name('trades.export');

    Route::resource('trades', TradeController::class);

    Route::resource('journals', DailyJournalController::class)->only(['index', 'show', 'store', 'update']);

    Route::get('/statistics', [StatisticController::class, 'index'])->name('statistics.index');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::resource('accounts', TradingAccountController::class)->except(['show']);
        Route::post('instruments/sync', [InstrumentController::class, 'syncFromProvider'])->name('instruments.sync');
        Route::get('instruments/prices', [InstrumentController::class, 'prices'])->name('instruments.prices');
        Route::resource('instruments', InstrumentController::class)->except(['show']);
        Route::resource('setups', SetupController::class)->except(['show']);

        Route::get('/targets', [MonthlyTargetController::class, 'index'])->name('targets.index');
        Route::post('/targets', [MonthlyTargetController::class, 'store'])->name('targets.store');
        Route::put('/targets/{monthlyTarget}', [MonthlyTargetController::class, 'update'])->name('targets.update');
    });

    Route::post('/switch-account', [TradingAccountController::class, 'switchActive'])->name('accounts.switch');
});



require __DIR__.'/auth.php';
