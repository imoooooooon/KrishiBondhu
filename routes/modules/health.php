<?php

use App\Http\Controllers\PlannedController;
use App\Http\Controllers\SoilGuidanceController;
use Illuminate\Support\Facades\Route;

/*
| Crop and animal health guidance, veterinary services, treatment history.
| IA.md §5.6. Features: F15–F19.
| Access: hub and vet directory are public; guidance and treatments need auth; /vet/* needs the veterinarian role.
*/

// Health hub (public): entry point to F15–F19
Route::get('/health', PlannedController::class)->name('health.index');

// Soil-based fertilizer guidance — F15. Signed in here; the Farmer role and ownership are checked by SoilGuidanceRecordPolicy.
// Each new request and each retry calls the AI service, so both are rate limited per account (5 and 6 per minute).
Route::middleware('auth')->group(function () {
    Route::resource('guidance/soil', SoilGuidanceController::class)->only(['index', 'create', 'show'])->names('guidance.soil')->parameters(['soil' => 'record']);
    Route::post('/guidance/soil', [SoilGuidanceController::class, 'store'])->middleware('throttle:5,1,soil-guidance-create')->name('guidance.soil.store');
    Route::post('/guidance/soil/{record}/retry', [SoilGuidanceController::class, 'retry'])->middleware('throttle:6,1,soil-guidance-retry')->name('guidance.soil.retry');
    Route::get('/guidance/soil/{record}/photo', [SoilGuidanceController::class, 'photo'])->name('guidance.soil.photo');
});

// AI guidance: crop disease, animal health — F16, F17
Route::resource('guidance/crop-health', PlannedController::class)->only(['index', 'create', 'store', 'show'])->names('guidance.crop-health')->parameters(['crop-health' => 'record']);
Route::resource('guidance/animal-health', PlannedController::class)->only(['index', 'create', 'store', 'show'])->names('guidance.animal-health')->parameters(['animal-health' => 'record']);

// Veterinary search and visit requests — F18
Route::get('/vets', PlannedController::class)->name('vets.index');
Route::get('/vets/{vet}', PlannedController::class)->name('vets.show');
Route::post('/vets/{vet}/visit-requests', PlannedController::class)->name('vets.visit-requests.store');
Route::get('/visit-requests', PlannedController::class)->name('visit-requests.index');
Route::get('/visit-requests/{visitRequest}', PlannedController::class)->name('visit-requests.show');

// Veterinarian workspace — F18
Route::get('/vet/profile', PlannedController::class)->name('vet.profile.edit');
Route::patch('/vet/profile', PlannedController::class)->name('vet.profile.update');
Route::get('/vet/visit-requests', PlannedController::class)->name('vet.visit-requests.index');
Route::patch('/vet/visit-requests/{visitRequest}', PlannedController::class)->name('vet.visit-requests.update');

// Treatment history and follow-ups — F19 (reminders: reminders.index in account.php)
Route::resource('treatments', PlannedController::class);
