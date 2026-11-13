<?php

use App\Http\Controllers\AgriculturalProductPhotoController;
use App\Http\Controllers\PlannedController;
use App\Http\Controllers\Seller\AgriculturalProductController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

/*
| Agricultural product sales (fertilizer, pesticide, tools).
| IA.md §5.8. Feature: F25 (cart, orders and payments are shared with marketplace.php).
| Access: browsing is public; /seller/* needs the agricultural product seller role.
*/

// Browse farm supplies (public) — F25
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/products/{product}', [ShopController::class, 'show'])->name('shop.show');

// Product photo — F25. Private files: the controller checks the product's visibility on every request.
Route::get('/shop/products/{product}/photo/{variant}', [AgriculturalProductPhotoController::class, 'show'])
    ->whereIn('variant', ['thumb', 'display'])->name('shop.photo');

// Bulk offer on a product (continues in negotiations.*) — F25, F3. Waits for F3's PriceOffer.
Route::post('/shop/products/{product}/offers', PlannedController::class)->name('shop.offers.store');

// Seller workspace: manage products — F25. Signed in here; the seller role and ownership are checked by
// AgriculturalProductPolicy. Writes are rate limited per account: 10 new products and 30 changes per minute.
Route::middleware('auth')->group(function () {
    Route::resource('seller/products', AgriculturalProductController::class)->names('seller.products')
        ->parameters(['products' => 'product'])->only(['index', 'create', 'show', 'edit']);
    Route::post('/seller/products', [AgriculturalProductController::class, 'store'])
        ->middleware('throttle:10,1,seller-product-create')->name('seller.products.store');
    Route::match(['put', 'patch'], '/seller/products/{product}', [AgriculturalProductController::class, 'update'])
        ->middleware('throttle:30,1,seller-product-change')->name('seller.products.update');
    Route::delete('/seller/products/{product}', [AgriculturalProductController::class, 'destroy'])
        ->middleware('throttle:30,1,seller-product-change')->name('seller.products.destroy');
});

// Seller workspace: received orders — F25, waits for F5's Order model.
Route::get('/seller/orders', PlannedController::class)->name('seller.orders.index');
Route::get('/seller/orders/{order}', PlannedController::class)->name('seller.orders.show');
Route::patch('/seller/orders/{order}/status', PlannedController::class)->name('seller.orders.status');
