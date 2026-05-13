<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ── Auth (guest only) ──────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Protected routes (đăng nhập mới dùng được) ────────────────────────
Route::middleware('auth')->group(function () {

    // Redirect / → /orders
    Route::get('/', fn() => redirect()->route('orders.index'));

    // ── ADMIN only: Master Data CRUD ──────────────────────────────────
    Route::middleware('role:ADMIN')->group(function () {
        Route::resource('agencies',   AgencyController::class);
        Route::resource('categories', CategoryController::class);
        Route::resource('items',      ItemController::class);
        Route::resource('users',      UserController::class);
    });

    // ── Price List: ADMIN full CRUD; AGENCY+FARMER chỉ xem ───────────
    Route::get('price-lists',           [PriceListController::class, 'index'])->name('price-lists.index');
    Route::get('price-lists/{price_list}', [PriceListController::class, 'show'])->name('price-lists.show');
    Route::middleware('role:ADMIN')->group(function () {
        Route::get('price-lists/create',              [PriceListController::class, 'create'])->name('price-lists.create');
        Route::post('price-lists',                    [PriceListController::class, 'store'])->name('price-lists.store');
        Route::get('price-lists/{price_list}/edit',  [PriceListController::class, 'edit'])->name('price-lists.edit');
        Route::put('price-lists/{price_list}',       [PriceListController::class, 'update'])->name('price-lists.update');
        Route::delete('price-lists/{price_list}',    [PriceListController::class, 'destroy'])->name('price-lists.destroy');
    });

    // ── Orders: ADMIN full; AGENCY/FARMER tạo; CUSTOMER xem ──────────
    Route::get('orders',                [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}',        [OrderController::class, 'show'])->name('orders.show');

    // Tạo đơn: ADMIN + AGENCY + FARMER
    Route::middleware('role:ADMIN,AGENCY,FARMER')->group(function () {
        Route::get('orders/create',  [OrderController::class, 'create'])->name('orders.create');
        Route::post('orders',        [OrderController::class, 'store'])->name('orders.store');
    });

    // Hủy đơn: ADMIN + AGENCY
    Route::middleware('role:ADMIN,AGENCY')->group(function () {
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    });

    // ── Inventory: ADMIN + AGENCY ─────────────────────────────────────
    Route::middleware('role:ADMIN,AGENCY')->group(function () {
        Route::get('inventories',           [InventoryController::class, 'index'])->name('inventories.index');
        Route::get('inventory-transactions', [InventoryController::class, 'transactions'])->name('inventory-transactions.index');
    });
});
