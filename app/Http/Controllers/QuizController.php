<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    /**
     * GET /quizzes
     * Optional: ?per_page=20  &  ?include=questions
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $include = $request->query('include');

        $query = Quiz::query()->select('id','title','duration_minutes','teacher_id','created_at');

        if ($include === 'questions') {
            $query->with(['questions:id,quiz_id,type,scoring,marks,data,text']);
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * POST /quizzes
     * Requires teacher/admin ability if using Sanctum.
     */
    public function store(Request $request)
    {
        // If you’re using Sanctum tokens, enforce role abilities.
        if ($request->user() && method_exists($request->user(), 'tokenCan')) {
            if (! ($request->user()->tokenCan('teacher') || $request->user()->tokenCan('admin'))) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $validated = $request->validate([
            'title'            => ['required','string','max:255'],
            'duration_minutes' => ['required','integer','min:1'],
            'teacher_id'       => ['required','exists:users,id'],
        ]);

        $quiz = Quiz::create($validated);

        return response()->json($quiz, 201);
    }

    /**
     * GET /quizzes/{quiz}
     * Optional: ?include=questions
     */
    public function show(Request $request, Quiz $quiz)
    {
        $include = $request->query('include');
        if ($include === 'questions') {
            $quiz->load(['questions:id,quiz_id,type,scoring,marks,data,text']);
        }

        return response()->json($quiz);
    }

    /**
     * PATCH /quizzes/{quiz}
     * Requires teacher/admin ability if using Sanctum.
     */
    public function update(Request $request, Quiz $quiz)
    {
        if ($request->user() && method_exists($request->user(), 'tokenCan')) {
            if (! ($request->user()->tokenCan('teacher') || $request->user()->tokenCan('admin'))) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $validated = $request->validate([
            'title'            => ['sometimes','string','max:255'],
            'duration_minutes' => ['sometimes','integer','min:1'],
            'teacher_id'       => ['sometimes','exists:users,id'],
        ]);

        $quiz->update($validated);

        return response()->json($quiz->fresh());
    }

    /**
     * DELETE /quizzes/{quiz}
     * Requires teacher/admin ability if using Sanctum.
     */
    public function destroy(Request $request, Quiz $quiz)
    {
        if ($request->user() && method_exists($request->user(), 'tokenCan')) {
            if (! ($request->user()->tokenCan('teacher') || $request->user()->tokenCan('admin'))) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $quiz->delete();

        // 204 No Content is conventional; JSON ok too.
        return response()->json(['deleted' => true], 200);
    }

    // The create()/edit() methods are for Blade forms; keeping no-ops is fine for API-only projects.
    public function create() {}
    public function edit(Quiz $quiz) {}
}
