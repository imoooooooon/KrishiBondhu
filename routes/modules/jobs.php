<?php

use App\Http\Controllers\FarmJobController;
use App\Http\Controllers\PlannedController;
use Illuminate\Support\Facades\Route;

/*
| Farm jobs, applications, worker groups, bookings and complaints.
| IA.md §5.7. Features: F20–F22.
| Access: job search is public; applying needs the agricultural worker role; /farmer/jobs/* needs the farmer role.
*/

// Job search (public) — F20
Route::get('/jobs', [FarmJobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [FarmJobController::class, 'show'])->name('jobs.show');

// Farmer: post and manage own jobs — F20. Signed in here; the Farmer role and ownership are checked by FarmJobPolicy.
// There is no delete route: a job is closed instead, so F21 applications and bookings can keep pointing at it.
Route::middleware('auth')->group(function () {
    Route::get('/farmer/jobs', [FarmJobController::class, 'myJobs'])->name('farmer.jobs.index');
    Route::get('/farmer/jobs/create', [FarmJobController::class, 'create'])->name('farmer.jobs.create'); // before {job}
    Route::post('/farmer/jobs', [FarmJobController::class, 'store'])->name('farmer.jobs.store');
    Route::get('/farmer/jobs/{job}', [FarmJobController::class, 'show'])->name('farmer.jobs.show');
    Route::get('/farmer/jobs/{job}/edit', [FarmJobController::class, 'edit'])->name('farmer.jobs.edit');
    Route::match(['put', 'patch'], '/farmer/jobs/{job}', [FarmJobController::class, 'update'])->name('farmer.jobs.update');
    Route::patch('/farmer/jobs/{job}/close', [FarmJobController::class, 'close'])->name('farmer.jobs.close');
});

// Applications, wage agreement and booking — F21
Route::post('/jobs/{job}/applications', PlannedController::class)->name('jobs.applications.store');
Route::get('/farmer/jobs/{job}/applications', PlannedController::class)->name('farmer.jobs.applications.index');
Route::patch('/farmer/jobs/{job}/applications/{application}', PlannedController::class)->name('farmer.jobs.applications.update');
Route::post('/farmer/jobs/{job}/bookings', PlannedController::class)->name('farmer.jobs.bookings.store');
Route::get('/worker/applications', PlannedController::class)->name('worker.applications.index');
Route::patch('/worker/applications/{application}', PlannedController::class)->name('worker.applications.update');

// Worker groups led by a group leader — F21
Route::resource('worker/groups', PlannedController::class)->names('worker.groups')->parameters(['groups' => 'group']);

// Bookings (farmer and worker views) — F21
Route::get('/bookings', PlannedController::class)->name('bookings.index');
Route::get('/bookings/{booking}', PlannedController::class)->name('bookings.show');
Route::post('/bookings/{booking}/cancel', PlannedController::class)->name('bookings.cancel');

// Job complaints (users) — F22. Admin handling is in admin.php.
Route::resource('complaints', PlannedController::class)->only(['index', 'create', 'store', 'show']);
