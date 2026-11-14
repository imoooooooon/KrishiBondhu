<?php

use App\Http\Controllers\Contributor\MarketPriceController;
use App\Http\Controllers\MarketPriceController as PublicMarketPriceController;
use App\Http\Controllers\PlannedController;
use Illuminate\Support\Facades\Route;

/*
| Market prices and market locations.
| IA.md §5.5. Features: F11–F14. Markets themselves are managed in admin.php.
| Access: viewing is public; /contributor/* needs the market price contributor role.
*/

// Price tracking, comparison and trends (public) — F11, F13, F14
Route::get('/market-prices', [PublicMarketPriceController::class, 'index'])->name('market-prices.index');
Route::get('/market-prices/compare', PlannedController::class)->name('market-prices.compare');
Route::get('/market-prices/trends', PlannedController::class)->name('market-prices.trends');

// Markets and distance (public) — F12. "map" must stay above {market}.
Route::get('/markets', PlannedController::class)->name('markets.index');
Route::get('/markets/map', PlannedController::class)->name('markets.map');
Route::get('/markets/{market}', PlannedController::class)->name('markets.show');

// Contributor price entry — F11. Signed in here; the contributor role, ownership and the 7-day correction window are
// checked by MarketPricePolicy. History is kept: "destroy" withdraws an entry (soft delete with a reason), it never
// erases it. Per account: 20 new prices and 30 corrections or withdrawals per minute.
Route::middleware('auth')->group(function () {
    Route::resource('contributor/prices', MarketPriceController::class)->names('contributor.prices')
        ->parameters(['prices' => 'price'])->only(['index', 'create', 'edit']);
    Route::post('/contributor/prices', [MarketPriceController::class, 'store'])
        ->middleware('throttle:20,1,market-price-create')->name('contributor.prices.store');
    Route::match(['put', 'patch'], '/contributor/prices/{price}', [MarketPriceController::class, 'update'])
        ->middleware('throttle:30,1,market-price-change')->name('contributor.prices.update');
    Route::delete('/contributor/prices/{price}', [MarketPriceController::class, 'destroy'])
        ->middleware('throttle:30,1,market-price-change')->name('contributor.prices.destroy');
});
