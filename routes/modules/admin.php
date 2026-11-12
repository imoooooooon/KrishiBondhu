<?php

use App\Http\Controllers\PlannedController;
use Illuminate\Support\Facades\Route;

/*
| Administration: users, listings, markets, complaints, platform activity.
| IA.md §5.9. Features: F22 (complaint handling); the rest supports the SRS "Administration" function.
| Access: administrator role only.
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', PlannedController::class)->name('dashboard');

    Route::get('/users', PlannedController::class)->name('users.index');
    Route::get('/users/{user}', PlannedController::class)->name('users.show');
    Route::patch('/users/{user}', PlannedController::class)->name('users.update');

    Route::get('/listings', PlannedController::class)->name('listings.index');
    Route::patch('/listings/{listing}', PlannedController::class)->name('listings.update');

    // Market list used by price contributors and maps — supports F11, F12
    Route::resource('markets', PlannedController::class)->except(['show']);

    // Complaint review and response — F22
    Route::get('/complaints', PlannedController::class)->name('complaints.index');
    Route::get('/complaints/{complaint}', PlannedController::class)->name('complaints.show');
    Route::patch('/complaints/{complaint}', PlannedController::class)->name('complaints.update');

    Route::get('/activity', PlannedController::class)->name('activity');
});
