<?php
//Author: Chong Pei Lee
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizApiController extends Controller
{
    /**
     * GET /api/v1/quizzes
     * Filters: ?teacher_id=&active=1&per_page=20
     */
    public function index(Request $request)
    {
        $q = Quiz::query()->select(
            'id','title','starts_at','ends_at','duration_minutes','teacher_id','is_active'
        );

        if ($request->filled('teacher_id')) {
            $q->where('teacher_id', (int) $request->input('teacher_id'));
        }

        if ($request->boolean('active')) {
            $now = now();
            $q->where('is_active', true)
              ->where(fn($w) => $w->whereNull('starts_at')->orWhere('starts_at','<=',$now))
              ->where(fn($w) => $w->whereNull('ends_at')->orWhere('ends_at','>=',$now));
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 20)));
        return response()->json($q->latest()->paginate($perPage));
    }

    /**
     * GET /api/v1/quizzes/{quiz}
     */
    public function show(Quiz $quiz)
    {
        $quiz->setVisible([
            'id','title','starts_at','ends_at','duration_minutes','teacher_id','is_active'
        ]);
        $quiz->load(['questions:id,quiz_id,type,scoring,marks,data']);

        return response()->json($quiz);
    }

    /**
     * POST /api/v1/quizzes
     * Require Sanctum token with teacher OR admin
     * teacher_id is taken from authenticated user (not from client)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user || (! $user->tokenCan('teacher') && ! $user->tokenCan('admin'))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'title'            => ['required','string','max:255'],
            'is_active'        => ['sometimes','boolean'],
            'starts_at'        => ['nullable','date'],
            'ends_at'          => ['nullable','date','after_or_equal:starts_at'],
            'duration_minutes' => ['required','integer','min:1','max:1000'],
        ]);

        $quiz = Quiz::create($validated + [
            'teacher_id' => $user->id,
        ]);

        return response()->json([
            'data' => $quiz->only('id','title','teacher_id','starts_at','ends_at','duration_minutes','is_active')
        ], 201);
    }

    /**
     * PATCH /api/v1/quizzes/{quiz}
     * Teachers can update ONLY their own quizzes; admin can update all.
     * Optional teacher_id change allowed only for admin.
     */
    public function update(Request $request, Quiz $quiz)
    {
        $user = $request->user();
        if (! $user || (! $user->tokenCan('teacher') && ! $user->tokenCan('admin'))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! $user->tokenCan('admin') && $quiz->teacher_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'title'            => ['sometimes','string','max:255'],
            'is_active'        => ['sometimes','boolean'],
            'starts_at'        => ['sometimes','nullable','date'],
            'ends_at'          => ['sometimes','nullable','date','after_or_equal:starts_at'],
            'duration_minutes' => ['sometimes','integer','min:1','max:1000'],
        ]);

        if ($user->tokenCan('admin') && $request->filled('teacher_id')) {
            $request->validate(['teacher_id' => ['exists:users,id']]);
            $data['teacher_id'] = (int) $request->input('teacher_id');
        }

        $quiz->update($data);

        return response()->json(
            $quiz->only('id','title','teacher_id','starts_at','ends_at','duration_minutes','is_active')
        );
    }

    /**
     * DELETE /api/v1/quizzes/{quiz}
     */
    public function destroy(Request $request, Quiz $quiz)
    {
        $user = $request->user();
        if (! $user || (! $user->tokenCan('teacher') && ! $user->tokenCan('admin'))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! $user->tokenCan('admin') && $quiz->teacher_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $quiz->delete();
        return response()->json(['deleted' => true]);
    }
}
