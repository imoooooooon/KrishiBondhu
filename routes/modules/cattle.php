<?php

use App\Http\Controllers\CattleController;
use App\Http\Controllers\CattleHealthEntryController;
use App\Http\Controllers\CattlePhotoController;
use App\Http\Controllers\PlannedController;
use Illuminate\Support\Facades\Route;

/*
| Cattle profiles, insemination records, lineage, breeding checks, pregnancy.
| IA.md §5.4. Features: F6–F10.
| Access: farmer role for own cattle; insemination agents only for cattle a farmer has shared with them.
*/

// Digital cattle profiles — F6. Private: signed in here; the Farmer role and ownership are checked by CattlePolicy.
// Writes are rate limited per account: 10 registrations and 30 other changes per minute.
Route::middleware('auth')->group(function () {
    Route::resource('cattle', CattleController::class)->parameters(['cattle' => 'cattle'])->only(['index', 'create', 'show', 'edit']);
    Route::post('/cattle', [CattleController::class, 'store'])->middleware('throttle:10,1,cattle-create')->name('cattle.store');
    Route::match(['put', 'patch'], '/cattle/{cattle}', [CattleController::class, 'update'])->middleware('throttle:30,1,cattle-change')->name('cattle.update');
    Route::delete('/cattle/{cattle}', [CattleController::class, 'destroy'])->middleware('throttle:30,1,cattle-change')->name('cattle.destroy');

    Route::get('/cattle/{cattle}/photo/{variant}', [CattlePhotoController::class, 'show'])->whereIn('variant', ['thumb', 'display'])->name('cattle.photo');

    Route::get('/cattle/{cattle}/health-history/create', [CattleHealthEntryController::class, 'create'])->name('cattle.health-history.create');
    Route::post('/cattle/{cattle}/health-history', [CattleHealthEntryController::class, 'store'])->middleware('throttle:30,1,cattle-change')->name('cattle.health-history.store');
    Route::get('/cattle/{cattle}/health-history/{entry}/edit', [CattleHealthEntryController::class, 'edit'])->name('cattle.health-history.edit');
    Route::match(['put', 'patch'], '/cattle/{cattle}/health-history/{entry}', [CattleHealthEntryController::class, 'update'])->middleware('throttle:30,1,cattle-change')->name('cattle.health-history.update');
});

// Parentage and family tree — F8
Route::get('/cattle/{cattle}/family-tree', PlannedController::class)->name('cattle.family-tree');
Route::put('/cattle/{cattle}/parents', PlannedController::class)->name('cattle.parents.update');

// Farmer shares a cow with an insemination agent — F7
Route::post('/cattle/{cattle}/agents', PlannedController::class)->name('cattle.agents.store');
Route::delete('/cattle/{cattle}/agents/{agent}', PlannedController::class)->name('cattle.agents.destroy');

// Insemination service records (history kept; no delete) — F7
Route::resource('cattle.inseminations', PlannedController::class)->shallow()->except(['destroy']);

// Agent workspace — F7
Route::get('/agent/cattle', PlannedController::class)->name('agent.cattle.index');
Route::get('/agent/inseminations', PlannedController::class)->name('agent.inseminations.index');

// Inbreeding warnings before a service — F9
Route::get('/breeding-check', PlannedController::class)->name('breeding-check.create');
Route::post('/breeding-check', PlannedController::class)->name('breeding-check.store');

// Pregnancy and calving — F10 (reminders: reminders.index in account.php)
Route::resource('cattle.pregnancies', PlannedController::class)->shallow()->except(['destroy']);
Route::post('/pregnancies/{pregnancy}/calving', PlannedController::class)->name('pregnancies.calving');
