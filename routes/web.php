<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\PlaysController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\RunController;
use App\Http\Controllers\StudioController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Explore
Route::get('/', [ExploreController::class, 'index'])->name('explore');

// Quiz page
Route::get('/q/{quiz:ulid}', [QuizController::class, 'show'])->name('quiz.show');

// Vote (rate-limited)
Route::post('/q/{quiz:ulid}/vote', [QuizController::class, 'vote'])
    ->middleware(['auth', 'throttle:votes'])
    ->name('quiz.vote');

// Run endpoints (rate-limited)
Route::post('/q/{quiz:ulid}/runs', [RunController::class, 'start'])
    ->middleware('throttle:runs')
    ->name('runs.start');

Route::post('/runs/{run}/answers', [RunController::class, 'answer'])->name('runs.answer');
Route::post('/runs/{run}/submit', [RunController::class, 'submit'])->name('runs.submit');

// My Plays
Route::get('/plays', [PlaysController::class, 'index'])
    ->middleware('auth')
    ->name('plays');

// Creator Studio
Route::prefix('studio')->middleware('auth')->name('studio.')->group(function () {
    Route::get('/', [StudioController::class, 'index'])->name('index');
    Route::get('/quizzes/create', [StudioController::class, 'create'])->name('quizzes.create');
    Route::post('/quizzes', [StudioController::class, 'store'])->name('quizzes.store');
    Route::get('/quizzes/{quiz:ulid}/edit', [StudioController::class, 'edit'])->name('quizzes.edit');
    Route::put('/quizzes/{quiz:ulid}', [StudioController::class, 'update'])->name('quizzes.update');
    Route::delete('/quizzes/{quiz:ulid}', [StudioController::class, 'destroy'])->name('quizzes.destroy');
    Route::post('/quizzes/{quiz:ulid}/publish', [StudioController::class, 'publish'])->name('quizzes.publish');
    Route::post('/quizzes/{quiz:ulid}/unpublish', [StudioController::class, 'unpublish'])->name('quizzes.unpublish');
    Route::post('/quizzes/{quiz:ulid}/duplicate', [StudioController::class, 'duplicate'])->name('quizzes.duplicate');
    Route::get('/quizzes/{quiz:ulid}/stats', [StudioController::class, 'stats'])->name('quizzes.stats');

    // Question CRUD
    Route::post('/quizzes/{quiz:ulid}/questions', [StudioController::class, 'storeQuestion'])->name('quizzes.questions.store');
    Route::put('/quizzes/{quiz:ulid}/questions/{questionId}', [StudioController::class, 'updateQuestion'])->name('quizzes.questions.update');
    Route::delete('/quizzes/{quiz:ulid}/questions/{questionId}', [StudioController::class, 'destroyQuestion'])->name('quizzes.questions.destroy');
});
