<?php

use App\Http\Controllers\PlannedController;
use Illuminate\Support\Facades\Route;

/*
| Crop marketplace, negotiation, cart, checkout, orders and payments.
| IA.md §5.3. Features: F1–F5, F26 (and F3 negotiation reused by F25).
| Access: browsing is public; buying needs auth; /farmer/* needs the farmer role.
*/

// Browse and search (public) — F2, F1
Route::get('/marketplace', PlannedController::class)->name('marketplace.index');
Route::get('/marketplace/crops/{listing}', PlannedController::class)->name('marketplace.show');

// Farmer: manage crop listings — F1
Route::resource('farmer/listings', PlannedController::class)->names('farmer.listings')->parameters(['listings' => 'listing']);

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
