<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:ADMIN')->group(function () {
        Route::resource('agencies', AgencyController::class);
        Route::resource('categories', CategoryController::class);
        Route::resource('items', ItemController::class);
        Route::resource('users', UserController::class);
    });

    Route::get('price-lists', [PriceListController::class, 'index'])->name('price-lists.index');
    Route::get('price-lists/{price_list}', [PriceListController::class, 'show'])
        ->whereNumber('price_list')
        ->name('price-lists.show');

    Route::middleware('role:ADMIN')->group(function () {
        Route::get('price-lists/create', [PriceListController::class, 'create'])->name('price-lists.create');
        Route::post('price-lists', [PriceListController::class, 'store'])->name('price-lists.store');
        Route::get('price-lists/{price_list}/edit', [PriceListController::class, 'edit'])
            ->whereNumber('price_list')
            ->name('price-lists.edit');
        Route::put('price-lists/{price_list}', [PriceListController::class, 'update'])
            ->whereNumber('price_list')
            ->name('price-lists.update');
        Route::delete('price-lists/{price_list}', [PriceListController::class, 'destroy'])
            ->whereNumber('price_list')
            ->name('price-lists.destroy');
    });

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])
        ->whereNumber('order')
        ->name('orders.show');

    Route::middleware('role:ADMIN,AGENCY,FARMER')->group(function () {
        Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    });

    Route::middleware('role:ADMIN,AGENCY')->group(function () {
        Route::post('orders/{order}/process', [OrderController::class, 'process'])
            ->whereNumber('order')
            ->name('orders.process');
        Route::post('orders/{order}/complete', [OrderController::class, 'complete'])
            ->whereNumber('order')
            ->name('orders.complete');
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])
            ->whereNumber('order')
            ->name('orders.cancel');
    });

    Route::middleware('role:ADMIN,AGENCY')->group(function () {
        Route::get('inventories', [InventoryController::class, 'index'])->name('inventories.index');
        Route::get('inventory-transactions', [InventoryController::class, 'transactions'])->name('inventory-transactions.index');
    });
});
