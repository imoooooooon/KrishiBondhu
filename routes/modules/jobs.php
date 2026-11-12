<?php

use App\Http\Controllers\PlannedController;
use Illuminate\Support\Facades\Route;

/*
| Farm jobs, applications, worker groups, bookings and complaints.
| IA.md §5.7. Features: F20–F22.
| Access: job search is public; applying needs the agricultural worker role; /farmer/jobs/* needs the farmer role.
*/

// Job search (public) — F20
Route::get('/jobs', PlannedController::class)->name('jobs.index');
Route::get('/jobs/{job}', PlannedController::class)->name('jobs.show');

// Farmer: post and manage jobs — F20
Route::resource('farmer/jobs', PlannedController::class)->names('farmer.jobs')->parameters(['jobs' => 'job']);

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
