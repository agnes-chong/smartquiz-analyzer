<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginApiController;
use App\Http\Controllers\Api\{
    QuizApiController,
    QuestionApiController,
    AttemptApiController
};

/**
 * ──────────────────────────────────────────────────────────────────────────────
 *  AUTH (Sanctum personal access tokens)
 *  - Login issues a Bearer token (use it in Postman: Authorization: Bearer <token>)
 *  - Versioned endpoint: /api/v1/login
 *  - Optional non-versioned alias: /api/login (uncomment if you want it)
 * ──────────────────────────────────────────────────────────────────────────────
 */
Route::post('/v1/login', LoginApiController::class);
// Route::post('/login', LoginApiController::class); // ← optional alias

/**
 * ──────────────────────────────────────────────────────────────────────────────
 *  API v1 (preferred)
 *  Final URLs are /api/v1/...
 * ──────────────────────────────────────────────────────────────────────────────
 */
Route::prefix('v1')->group(function () {
    /** -------------------- Public (no auth) -------------------- */
    Route::get('/quizzes', [QuizApiController::class, 'index']);                          // catalog
    Route::get('/quizzes/{quiz}', [QuizApiController::class, 'show']);                    // quiz details
    Route::get('/quizzes/{quiz}/questions', [QuestionApiController::class, 'index']);     // questions in quiz

    /** -------------------- Protected (Sanctum) -------------------- */
    Route::middleware('auth:sanctum')->group(function () {

        // Quick self-check
        Route::get('/me', fn (Request $r) => $r->user());

        /** ===== Students: Attempts ===== */
        // Start an attempt (both singular & plural for convenience)
        Route::post('/quizzes/{quiz}/attempt',  [AttemptApiController::class, 'store']);
        Route::post('/quizzes/{quiz}/attempts', [AttemptApiController::class, 'store']);

        // View an attempt
        Route::get('/attempts/{attempt}', [AttemptApiController::class, 'show']);

        // Submit/finish an attempt (canonical)
        Route::post('/attempts/{attempt}/finish', [AttemptApiController::class, 'finish']);

        // Teacher marks short answers
        Route::patch('/attempts/{attempt}/mark', [AttemptApiController::class, 'markShort']);

        // Alias used by your Postman tests
        Route::post('/attempts/{attempt}/response', [AttemptApiController::class, 'finish']);

        // Body-only variant: { "attempt_id": ..., "responses": {...} }
        Route::post('/attempts/finish', [AttemptApiController::class, 'finishByBody']);

        /** ===== Teachers/Admin: Quizzes ===== */
        Route::post('/quizzes',          [QuizApiController::class, 'store']);
        Route::patch('/quizzes/{quiz}',  [QuizApiController::class, 'update']);
        Route::delete('/quizzes/{quiz}', [QuizApiController::class, 'destroy']);

        /** ===== Teachers/Admin: Questions ===== */
        Route::post('/quizzes/{quiz}/questions', [QuestionApiController::class, 'store']);
        Route::patch('/questions/{question}',    [QuestionApiController::class, 'update']);
        Route::delete('/questions/{question}',   [QuestionApiController::class, 'destroy']);
    });
});

/**
 * ──────────────────────────────────────────────────────────────────────────────
 *  Back-compat aliases (no /v1 in path)
 *  Final URLs are /api/...
 *  Useful if your Postman collection currently hits /api/attempts/{id}.
 *  Safe to remove later once everything is migrated to /api/v1/...
 * ──────────────────────────────────────────────────────────────────────────────
 */
Route::middleware('auth:sanctum')->group(function () {
    // Self-check without version
    Route::get('/me', fn (Request $r) => $r->user());

    // Attempts (aliases)
    Route::get('/attempts/{attempt}',            [AttemptApiController::class, 'show']);
    Route::post('/attempts/{attempt}/finish',    [AttemptApiController::class, 'finish']);
    Route::post('/attempts/{attempt}/response',  [AttemptApiController::class, 'finish']);
    Route::post('/attempts/finish',              [AttemptApiController::class, 'finishByBody']);
});
