<?php

use App\Http\Controllers\PlannedController;
use Illuminate\Support\Facades\Route;

/*
| Cattle profiles, insemination records, lineage, breeding checks, pregnancy.
| IA.md §5.4. Features: F6–F10.
| Access: farmer role for own cattle; insemination agents only for cattle a farmer has shared with them.
*/

// Digital cattle profiles — F6
Route::resource('cattle', PlannedController::class)->parameters(['cattle' => 'cattle']);

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
