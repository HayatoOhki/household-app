<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OpeningBalanceController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionRuleController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get(
        '/',
        [DashboardController::class, 'index']
    )->name('dashboard');

    Route::resource(
        'accounts',
        AccountController::class
    )->except([
        'show',
    ]);

    Route::resource(
        'categories',
        CategoryController::class
    )->except([
        'show',
    ]);

    Route::resource(
        'transactions',
        TransactionController::class
    )->only([
        'index',
        'create',
        'store',
        'edit',
        'update',
        'destroy',
    ]);

    Route::get(
        '/transfers/create',
        [TransferController::class, 'create']
    )->name('transfers.create');

    Route::post(
        '/transfers',
        [TransferController::class, 'store']
    )->name('transfers.store');

    Route::get(
        '/transfers/{transfer}/edit',
        [TransferController::class, 'edit']
    )->name('transfers.edit');

    Route::put(
        '/transfers/{transfer}',
        [TransferController::class, 'update']
    )->name('transfers.update');

    Route::delete(
        '/transfers/{transfer}',
        [TransferController::class, 'destroy']
    )->name('transfers.destroy');

    Route::resource(
        'transaction-rules',
        TransactionRuleController::class
    )->except([
        'show',
    ]);

    Route::get(
        '/opening-balances/create',
        [OpeningBalanceController::class, 'create']
    )->name('opening-balances.create');

    Route::post(
        '/opening-balances',
        [OpeningBalanceController::class, 'store']
    )->name('opening-balances.store');

    Route::get(
        '/opening-balances/{openingBalance}/edit',
        [OpeningBalanceController::class, 'edit']
    )->name('opening-balances.edit');

    Route::put(
        '/opening-balances/{openingBalance}',
        [OpeningBalanceController::class, 'update']
    )->name('opening-balances.update');

    Route::delete(
        '/opening-balances/{openingBalance}',
        [OpeningBalanceController::class, 'destroy']
    )->name('opening-balances.destroy');

    Route::get(
        '/summary',
        [SummaryController::class, 'index']
    )->name('summary.index');
});