<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\UserController;

Route::resource('agencies', AgencyController::class);
Route::resource('categories', CategoryController::class);
Route::resource('items', ItemController::class);
Route::resource('price-lists', PriceListController::class);
Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show']);
Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

Route::resource('users', UserController::class);

Route::get('inventories', [InventoryController::class, 'index'])->name('inventories.index');
Route::get('inventory-transactions', [InventoryController::class, 'transactions'])->name('inventory-transactions.index');



