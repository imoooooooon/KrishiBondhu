<?php

use App\Http\Controllers\PlannedController;
use Illuminate\Support\Facades\Route;

/*
| Accounts, roles, preferences, notifications, reminders and voice.
| IA.md §5.2. Supporting: login/registration are not counted as features.
| Features: F24 (language, low-data preferences), F23 (voice), F10/F19 (reminders).
| Access when auth exists: guest group → middleware('guest'); rest → middleware('auth').
*/

// Authentication (guest)
Route::get('/login', PlannedController::class)->name('login');
Route::post('/login', PlannedController::class)->name('login.store');
Route::get('/register', PlannedController::class)->name('register');
Route::post('/register', PlannedController::class)->name('register.store');
Route::get('/forgot-password', PlannedController::class)->name('password.request');
Route::post('/forgot-password', PlannedController::class)->name('password.email');
Route::get('/reset-password/{token}', PlannedController::class)->name('password.reset');
Route::post('/reset-password', PlannedController::class)->name('password.update');

// Session (auth)
Route::post('/logout', PlannedController::class)->name('logout');
Route::get('/dashboard', PlannedController::class)->name('dashboard');

// Account (auth)
Route::get('/account/profile', PlannedController::class)->name('profile.edit');
Route::patch('/account/profile', PlannedController::class)->name('profile.update');
Route::get('/account/roles', PlannedController::class)->name('roles.index');
Route::post('/account/roles', PlannedController::class)->name('roles.store');
Route::delete('/account/roles/{role}', PlannedController::class)->name('roles.destroy');
Route::get('/account/preferences', PlannedController::class)->name('preferences.edit');      // F24
Route::patch('/account/preferences', PlannedController::class)->name('preferences.update');  // F24

// Language switch (guest and auth)
Route::post('/preferences/locale', PlannedController::class)->name('preferences.locale');    // F24

// In-site notifications and reminders (auth)
Route::get('/notifications', PlannedController::class)->name('notifications.index');
Route::patch('/notifications/{notification}/read', PlannedController::class)->name('notifications.read');
Route::post('/notifications/read-all', PlannedController::class)->name('notifications.read-all');
Route::get('/reminders', PlannedController::class)->name('reminders.index');                  // F10, F19
Route::patch('/reminders/{reminder}/done', PlannedController::class)->name('reminders.complete'); // F10, F19

// Bangla voice (auth; JSON responses for the browser, not a separate API)
Route::post('/voice/transcribe', PlannedController::class)->name('voice.transcribe');        // F23
Route::post('/voice/speak', PlannedController::class)->name('voice.speak');                  // F23
