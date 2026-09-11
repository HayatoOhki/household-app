<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OpeningBalanceController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionRuleController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', function () {
        return view('home');
    })->name('home');

    Route::resource('accounts', AccountController::class)
        ->except(['show']);

    Route::resource('categories', CategoryController::class)
        ->except(['show']);

    Route::resource('transactions', TransactionController::class)
        ->only([
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
});