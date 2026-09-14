<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OpeningBalanceController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionRuleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get(
        '/',
        [DashboardController::class, 'index']
    )->name('dashboard');

    Route::get(
        '/accounts',
        [AccountController::class, 'index']
    )->name('accounts.index');

    Route::put(
        '/accounts/bulk-update',
        [AccountController::class, 'bulkUpdate']
    )->name('accounts.bulk-update');

    Route::delete(
        '/accounts/{account}',
        [AccountController::class, 'destroy']
    )->name('accounts.destroy');

    Route::get(
        '/categories',
        [CategoryController::class, 'index']
    )->name('categories.index');

    Route::put(
        '/categories/bulk-update',
        [CategoryController::class, 'bulkUpdate']
    )->name('categories.bulk-update');

    Route::delete(
        '/categories/{category}',
        [CategoryController::class, 'destroy']
    )->name('categories.destroy');

    Route::patch(
        '/transactions/{transaction}/status',
        [TransactionController::class, 'updateStatus']
    )->name('transactions.status');

    Route::get(
        '/transactions/{transaction}/duplicate',
        [TransactionController::class, 'duplicate']
    )->name('transactions.duplicate');

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