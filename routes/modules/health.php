<?php

use App\Http\Controllers\PlannedController;
use App\Http\Controllers\SoilGuidanceController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\VetDirectoryController;
use App\Http\Controllers\VetProfileController;
use App\Http\Controllers\VetVisitRequestController;
use App\Http\Controllers\VisitRequestController;
use Illuminate\Support\Facades\Route;

/*
| Crop and animal health guidance, veterinary services, treatment history.
| IA.md §5.6. Features: F15–F19.
| Access: hub and vet directory are public; guidance and treatments need auth; /vet/* needs the veterinarian role.
*/

// Health hub (public): entry point to F15–F19. A plain page of links, so a view route is enough.
Route::view('/health', 'health.index')->name('health.index');

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
Route::get('/vets', [VetDirectoryController::class, 'index'])->name('vets.index');
Route::get('/vets/{vet}', [VetDirectoryController::class, 'show'])->name('vets.show');

Route::middleware('auth')->group(function () {
    Route::post('/vets/{vet}/visit-requests', [VisitRequestController::class, 'store'])->name('vets.visit-requests.store');
    Route::get('/visit-requests', [VisitRequestController::class, 'index'])->name('visit-requests.index');
    Route::get('/visit-requests/{visitRequest}', [VisitRequestController::class, 'show'])->name('visit-requests.show');
    Route::patch('/visit-requests/{visitRequest}/cancel', [VisitRequestController::class, 'cancel'])->name('visit-requests.cancel');

    // Veterinarian workspace — F18
    Route::get('/vet/profile', [VetProfileController::class, 'edit'])->name('vet.profile.edit');
    Route::patch('/vet/profile', [VetProfileController::class, 'update'])->name('vet.profile.update');
    Route::get('/vet/visit-requests', [VetVisitRequestController::class, 'index'])->name('vet.visit-requests.index');
    Route::patch('/vet/visit-requests/{visitRequest}', [VetVisitRequestController::class, 'update'])->name('vet.visit-requests.update');
});

// Treatment history and follow-ups — F19 (reminders: reminders.index in account.php)
Route::middleware('auth')->group(function () {
    Route::resource('treatments', TreatmentController::class);
});
