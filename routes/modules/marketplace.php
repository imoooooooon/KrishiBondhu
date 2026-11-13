<?php

use App\Http\Controllers\CropListingPhotoController;
use App\Http\Controllers\Farmer\CropListingController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\PlannedController;
use Illuminate\Support\Facades\Route;

/*
| Crop marketplace, negotiation, cart, checkout, orders and payments.
| IA.md §5.3. Features: F1–F5, F26 (and F3 negotiation reused by F25).
| Access: browsing is public; buying needs auth; /farmer/* needs the farmer role.
*/

// Browse (public) — F1 now; F2 adds search and filters to the same pages.
Route::get('/marketplace', [MarketplaceController::class, 'index'])->name('marketplace.index');
Route::get('/marketplace/crops/{listing}', [MarketplaceController::class, 'show'])->name('marketplace.show');

// Crop listing photos — F1. Private files: the controller checks the listing's visibility on every request.
Route::get('/crop-listing-photos/{photo}/{variant}', [CropListingPhotoController::class, 'show'])
    ->whereNumber('photo')->whereIn('variant', ['thumb', 'display'])->name('crop-listing-photos.show');

// Farmer: manage crop listings — F1. Signed in here; the Farmer role and ownership are checked by CropListingPolicy.
// Writes are rate limited per account (spec §16): 10 new listings and 30 changes per minute.
Route::middleware('auth')->group(function () {
    Route::resource('farmer/listings', CropListingController::class)->names('farmer.listings')
        ->parameters(['listings' => 'listing'])->only(['index', 'create', 'show', 'edit']);
    Route::post('/farmer/listings', [CropListingController::class, 'store'])
        ->middleware('throttle:10,1,crop-listing-create')->name('farmer.listings.store');
    Route::match(['put', 'patch'], '/farmer/listings/{listing}', [CropListingController::class, 'update'])
        ->middleware('throttle:30,1,crop-listing-change')->name('farmer.listings.update');
    Route::delete('/farmer/listings/{listing}', [CropListingController::class, 'destroy'])
        ->middleware('throttle:30,1,crop-listing-change')->name('farmer.listings.destroy');
});

// Farmer: incoming orders — F5
Route::get('/farmer/orders', PlannedController::class)->name('farmer.orders.index');
Route::get('/farmer/orders/{order}', PlannedController::class)->name('farmer.orders.show');
Route::patch('/farmer/orders/{order}/status', PlannedController::class)->name('farmer.orders.status');

// Bulk requests and farmer bids — F2
Route::resource('bulk-requests', PlannedController::class)->only(['index', 'create', 'store', 'show'])->parameters(['bulk-requests' => 'bulkRequest']);
Route::patch('/bulk-requests/{bulkRequest}/close', PlannedController::class)->name('bulk-requests.close');
Route::post('/bulk-requests/{bulkRequest}/bids', PlannedController::class)->name('bulk-requests.bids.store');
Route::post('/bulk-requests/{bulkRequest}/bids/{bid}/accept', PlannedController::class)->name('bulk-requests.bids.accept');

// Limited bulk price negotiation — F3 (crop offers here; product offers in shop.php)
Route::post('/marketplace/crops/{listing}/offers', PlannedController::class)->name('marketplace.offers.store');
Route::get('/negotiations', PlannedController::class)->name('negotiations.index');
Route::get('/negotiations/{negotiation}', PlannedController::class)->name('negotiations.show');
Route::post('/negotiations/{negotiation}/counter', PlannedController::class)->name('negotiations.counter');
Route::post('/negotiations/{negotiation}/accept', PlannedController::class)->name('negotiations.accept');
Route::post('/negotiations/{negotiation}/reject', PlannedController::class)->name('negotiations.reject');

// Cart (crops and farm supplies share one cart) — F5, F25
Route::get('/cart', PlannedController::class)->name('cart.show');
Route::post('/cart/items', PlannedController::class)->name('cart.items.store');
Route::patch('/cart/items/{item}', PlannedController::class)->name('cart.items.update');
Route::delete('/cart/items/{item}', PlannedController::class)->name('cart.items.destroy');

// Delivery choice and full cost review — F4
Route::get('/checkout', PlannedController::class)->name('checkout.show');
Route::post('/checkout', PlannedController::class)->name('checkout.store');

// Buyer orders — F5
Route::get('/orders', PlannedController::class)->name('orders.index');
Route::get('/orders/{order}', PlannedController::class)->name('orders.show');
Route::post('/orders/{order}/cancel', PlannedController::class)->name('orders.cancel');

// Online payments (provider sandbox) — F26
Route::post('/orders/{order}/payments', PlannedController::class)->name('payments.store');
Route::get('/payments/{payment}', PlannedController::class)->name('payments.show');
// Provider callbacks: exclude from CSRF in bootstrap/app.php when implemented (IA.md §6).
Route::post('/payments/callback/success', PlannedController::class)->name('payments.callback.success');
Route::post('/payments/callback/fail', PlannedController::class)->name('payments.callback.fail');
Route::post('/payments/callback/cancel', PlannedController::class)->name('payments.callback.cancel');
Route::post('/payments/ipn', PlannedController::class)->name('payments.ipn');
