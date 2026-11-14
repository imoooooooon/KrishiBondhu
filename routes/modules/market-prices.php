<?php

use App\Http\Controllers\Contributor\MarketPriceController;
use App\Http\Controllers\PlannedController;
use Illuminate\Support\Facades\Route;

/*
| Market prices and market locations.
| IA.md §5.5. Features: F11–F14. Markets themselves are managed in admin.php.
| Access: viewing is public; /contributor/* needs the market price contributor role.
*/

// Price tracking, comparison and trends (public) — F11, F13, F14
Route::get('/market-prices', PlannedController::class)->name('market-prices.index');
Route::get('/market-prices/compare', PlannedController::class)->name('market-prices.compare');
Route::get('/market-prices/trends', PlannedController::class)->name('market-prices.trends');

// Markets and distance (public) — F12. "map" must stay above {market}.
Route::get('/markets', PlannedController::class)->name('markets.index');
Route::get('/markets/map', PlannedController::class)->name('markets.map');
Route::get('/markets/{market}', PlannedController::class)->name('markets.show');

// Contributor price entry (history kept; no delete) — F11. Signed in here; the contributor role (and, for corrections,
// ownership) is checked by MarketPricePolicy. Per account: 20 new prices per minute.
Route::middleware('auth')->group(function () {
    Route::resource('contributor/prices', MarketPriceController::class)->names('contributor.prices')
        ->parameters(['prices' => 'price'])->only(['index', 'create']);
    Route::post('/contributor/prices', [MarketPriceController::class, 'store'])
        ->middleware('throttle:20,1,market-price-create')->name('contributor.prices.store');
});
// Corrections (slice 2) — F11
Route::get('/contributor/prices/{price}/edit', PlannedController::class)->name('contributor.prices.edit');
Route::match(['put', 'patch'], '/contributor/prices/{price}', PlannedController::class)->name('contributor.prices.update');
