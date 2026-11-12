<?php

use App\Http\Controllers\PlannedController;
use Illuminate\Support\Facades\Route;

/*
| Agricultural product sales (fertilizer, pesticide, tools).
| IA.md §5.8. Feature: F25 (cart, orders and payments are shared with marketplace.php).
| Access: browsing is public; /seller/* needs the agricultural product seller role.
*/

// Browse farm supplies (public) — F25
Route::get('/shop', PlannedController::class)->name('shop.index');
Route::get('/shop/products/{product}', PlannedController::class)->name('shop.show');

// Bulk offer on a product (continues in negotiations.*) — F25, F3
Route::post('/shop/products/{product}/offers', PlannedController::class)->name('shop.offers.store');

// Seller workspace — F25
Route::resource('seller/products', PlannedController::class)->names('seller.products')->parameters(['products' => 'product']);
Route::get('/seller/orders', PlannedController::class)->name('seller.orders.index');
Route::get('/seller/orders/{order}', PlannedController::class)->name('seller.orders.show');
Route::patch('/seller/orders/{order}/status', PlannedController::class)->name('seller.orders.status');
