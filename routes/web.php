<?php

use App\Http\Controllers\ElearnController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ElearnController::class, 'home'])->name('home');
Route::get('/api/health', [ElearnController::class, 'health']);
Route::post('/api/login', [ElearnController::class, 'login'])->middleware('throttle:elearn-login');
Route::post('/api/logout', [ElearnController::class, 'logout']);
Route::middleware('auth')->group(function () {
    Route::get('/api/session', [ElearnController::class, 'session']);
    Route::get('/api/state', [ElearnController::class, 'state']);
    Route::put('/api/state', [ElearnController::class, 'updateState']);
    Route::get('/api/reports/course-summary', [ElearnController::class, 'courseSummary']);
    Route::get('/api/reports/submission-summary', [ElearnController::class, 'submissionSummary']);
    Route::get('/api/schema', [ElearnController::class, 'schema']);
    Route::post('/api/reset', [ElearnController::class, 'resetDemo']);
});
