<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\AttemptController;
use App\Http\Controllers\AuditLogController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Public landing goes to resources/views/home.blade.php
| Auth scaffolding handles /login, /register, password reset, etc.
| App pages (dashboard, quizzes) are protected behind auth middleware.
|--------------------------------------------------------------------------
*/

// Public landing page (your pastel homepage)
Route::view('/', 'home')->name('landing');

// Laravel auth routes (login, register, password reset, etc.)
Auth::routes([
    // toggle if you need email verification:
    // 'verify' => true,
]);

// Auth-protected pages
Route::middleware(['auth'])->group(function () {

    // After-login page (change the view if you use a different dashboard)
    Route::view('/dashboard', 'assessment.home')->name('dashboard');

    // Quizzes CRUD (web controllers, session auth)
    Route::resource('quizzes', QuizController::class);
});

// Optional: fallback to landing for unknown routes (or make a 404 view)
Route::fallback(function () {
    return redirect()->route('landing');
});

/**
 * Audit Logs – single route, protected.
 * Combine your intended middlewares to keep original intent:
 * - must be logged in
 * - must be admin
 * - must pass policy 'viewAny' on App\Models\AuditLog
 */
Route::middleware(['auth', 'admin', 'can:viewAny,App\Models\AuditLog'])
    ->get('/audit-logs', [AuditLogController::class, 'index'])
    ->name('audit.logs.index');

/**
 * Teacher-only routes
 * Avoid duplicate resource definitions by restricting this to write ops,
 * since a full resource for 'quizzes' already exists above.
 */
Route::middleware(['auth', 'teacher'])->group(function () {
    Route::resource('quizzes', QuizController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('questions', QuestionController::class);
});

/**
 * Attempts (student flow)
 */
Route::middleware(['auth'])->group(function () {
    Route::get('quizzes/{quiz}/take', [AttemptController::class, 'create'])->name('attempts.create');
    Route::post('quizzes/{quiz}/attempts', [AttemptController::class, 'store'])->name('attempts.store');
});
