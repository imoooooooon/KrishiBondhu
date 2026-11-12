<?php

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

// Contributor price entry (history kept; no delete) — F11
Route::resource('contributor/prices', PlannedController::class)->names('contributor.prices')->parameters(['prices' => 'price'])->except(['show', 'destroy']);
