<?php

use App\Http\Controllers\PlannedController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes — Krishi Bondhu
|--------------------------------------------------------------------------
| The full route map, with features, owners and access rules, is docs/IA.md.
| Each product area has its own file in routes/modules/ so teammates rarely
| edit the same file. Routes still pointing at PlannedController are planned:
| replace the controller when you build the page and keep the URI and name.
| The framework health check is registered at /up in bootstrap/app.php.
*/

// Landing page (basic). Owners of the home/dashboard IA item may extend it.
Route::view('/', 'home')->name('home');

// Living design-system reference (docs/design-system.md).
Route::view('/design-system', 'design-system.index')->name('design-system');

require __DIR__.'/modules/account.php';
require __DIR__.'/modules/marketplace.php';
require __DIR__.'/modules/cattle.php';
require __DIR__.'/modules/market-prices.php';
require __DIR__.'/modules/health.php';
require __DIR__.'/modules/jobs.php';
require __DIR__.'/modules/shop.php';
require __DIR__.'/modules/admin.php';
