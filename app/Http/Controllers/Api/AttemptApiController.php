<?php
// Author: Chong Pei Lee
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\AttemptAnswer;

use App\Contracts\ScoringStrategy;
use App\Strategies\Scoring\ExactMatchStrategy;
use App\Strategies\Scoring\PartialCreditStrategy;
use App\Strategies\Scoring\NegativeMarkingStrategy;

class AttemptApiController extends Controller
{
    /** Choose Strategy by question->scoring (single-pattern: Strategy) */
    private function pickStrategy(Question $q): ScoringStrategy
    {
        return match ($q->scoring) {
            'partial'  => app(PartialCreditStrategy::class),
            'negative' => app(NegativeMarkingStrategy::class),
            default    => app(ExactMatchStrategy::class),
        };
    }

    /**
     * Start a new attempt (student)
     * POST /api/v1/quizzes/{quiz}/attempt(s)
     */
    public function store(Request $request, int $quizId)
    {
        $quiz = Quiz::findOrFail($quizId);

        // 🔒 enforce availability window
        $now = now();
        if (!$quiz->is_active ||
            ($quiz->starts_at && $now->lt($quiz->starts_at)) ||
            ($quiz->ends_at && $now->gt($quiz->ends_at))) {
            return response()->json(['message' => 'Quiz is not open'], 422);
        }

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $request->user()->id,
            'score' => 0,
            'detail' => null,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        return response()->json(['success' => true, 'attempt' => $attempt], 201);
    }

    /**
     * Submit/finish an attempt (owner only).
     * POST /api/v1/attempts/{attempt}/finish
     */
    public function finish(Request $request, QuizAttempt $attempt)
    {
        // Ownership gate
        if ($attempt->student_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($attempt->completed_at !== null) {
            throw ValidationException::withMessages([
                'attempt' => ['This attempt has already been submitted.'],
            ]);
        }

        $quiz = Quiz::with(['questions.answers'])->findOrFail($attempt->quiz_id);

        // ⏱️ duration guard
        if ($attempt->started_at && $quiz->duration_minutes) {
            $elapsed = $attempt->started_at->diffInMinutes(now());
            if ($elapsed > $quiz->duration_minutes) {
                return response()->json(['message' => 'Time is up'], 422);
            }
        }

        // Decode "responses"
        $raw = $request->input('responses');
        if ($raw === null) {
            throw ValidationException::withMessages([
                'responses' => ['The responses field is required.'],
            ]);
        }
        if (is_string($raw)) {
            $responses = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $responses = $raw;
        } else {
            $responses = null;
        }
        if (!is_array($responses)) {
            throw ValidationException::withMessages([
                'responses' => ['Responses must be an object/array.'],
            ]);
        }

        $questions = $quiz->questions;
        $detail = [
            'quiz_id'      => $quiz->id,
            'items'        => [],
            'total'        => $questions->count(),
            'correct'      => 0,
            'incorrect'    => 0,
            'skipped'      => 0,
            'marks_total'  => 0.0,
        ];
        $scoreCorrect = 0;

        DB::transaction(function () use ($attempt, $questions, $responses, &$detail, &$scoreCorrect) {

            foreach ($questions as $q) {
                $qid = (string) $q->id;
                $given = $responses[$qid] ?? null;
                $qMarks = (float) ($q->marks ?? 1.0);

                // Normalize for arrays (MCQ/TF)
                $givenIds = is_array($given) ? array_map('strval', $given)
                    : ((is_null($given) || $given === '') ? [] : [(string) $given]);

                /* -------- SHORT (manual) → pending -------- */
                if ($q->type === 'short' && ($q->scoring ?? 'manual') === 'manual') {
                    $text = is_string($given) ? trim($given) : null;

                    AttemptAnswer::create([
                        'attempt_id'     => $attempt->id,
                        'question_id'    => $q->id,
                        'answer_id'      => null,
                        'response_text'  => $text,
                        'is_correct'     => null,     // pending
                        'awarded_marks'  => null,     // pending
                        'marking_method' => 'manual',
                    ]);

                    $detail['items'][] = [
                        'question_id'     => $q->id,
                        'given_answer'    => $text,
                        'correct_answers' => [],
                        'is_correct'      => false,
                        'status'          => 'pending',
                        'awarded_marks'   => 0,
                    ];
                    $detail['skipped']++;
                    continue;
                }

                /* -------- MCQ/TF or SHORT (auto) via Strategy -------- */
                if (in_array($q->type, ['mcq','tf'])) {
                    // ensure selected IDs belong to the question
                    $validIds = $q->answers->pluck('id')->map(fn($x)=>(string)$x)->all();
                    $givenIds = array_values(array_filter($givenIds, fn($aid)=>in_array($aid, $validIds, true)));
                }

                // Prepare input to strategy
                $givenForStrategy = ($q->type === 'short')
                    ? (is_string($given) ? $given : null)
                    : $givenIds;

                // Skipped (no selection / empty string)
                if (($q->type !== 'short' && empty($givenIds)) || ($q->type==='short' && ($givenForStrategy===null || $givenForStrategy===''))) {
                    AttemptAnswer::create([
                        'attempt_id'     => $attempt->id,
                        'question_id'    => $q->id,
                        'answer_id'      => null,
                        'response_text'  => $q->type==='short' ? ($givenForStrategy ?? null) : null,
                        'is_correct'     => false,
                        'awarded_marks'  => 0,
                        'marking_method' => 'auto',
                    ]);

                    $detail['items'][] = [
                        'question_id'     => $q->id,
                        'given_answer'    => $q->type==='short' ? ($givenForStrategy ?? null) : null,
                        'correct_answers' => $q->type==='short'
                            ? ($q->answer ?? [])
                            : $q->answers->where('is_correct',1)->pluck('id')->map(fn($x)=>(string)$x)->values()->toArray(),
                        'is_correct'      => false,
                        'status'          => 'skipped',
                        'awarded_marks'   => 0,
                    ];
                    $detail['skipped']++;
                    continue;
                }

                // Strategy compute
                $strategy = $this->pickStrategy($q);
                $awarded  = (float) $strategy->score($q, $givenForStrategy);
                $isCorrect = $awarded >= $qMarks;

                // Persist attempt answers
                if (in_array($q->type, ['mcq','tf'])) {
                    $rows = [];
                    $correctIds = $q->answers->where('is_correct',1)->pluck('id')->map(fn($x)=>(string)$x)->all();
                    foreach ($givenIds as $aid) {
                        $rows[] = AttemptAnswer::create([
                            'attempt_id'     => $attempt->id,
                            'question_id'    => $q->id,
                            'answer_id'      => (int) $aid,
                            'response_text'  => null,
                            'is_correct'     => in_array($aid, $correctIds, true),
                            'awarded_marks'  => 0,
                            'marking_method' => 'auto',
                        ]);
                    }
                    if (!empty($rows)) {
                        $first = $rows[0];
                        $first->awarded_marks = $awarded;
                        $first->save();
                    }
                } else { // short auto
                    AttemptAnswer::create([
                        'attempt_id'     => $attempt->id,
                        'question_id'    => $q->id,
                        'answer_id'      => null,
                        'response_text'  => (string)$givenForStrategy,
                        'is_correct'     => $isCorrect,
                        'awarded_marks'  => $awarded,
                        'marking_method' => 'auto',
                    ]);
                }

                // Detail row
                $correctIds = in_array($q->type,['mcq','tf'])
                    ? $q->answers->where('is_correct',1)->pluck('id')->map(fn($x)=>(string)$x)->values()->toArray()
                    : ($q->answer ?? []);

                $detail['items'][] = [
                    'question_id'     => $q->id,
                    'given_answer'    => $q->type==='short' ? (string)$givenForStrategy : $givenIds,
                    'correct_answers' => $correctIds,
                    'is_correct'      => $isCorrect,
                    'status'          => $isCorrect ? 'correct' : 'incorrect',
                    'awarded_marks'   => $awarded,
                ];

                $detail['marks_total'] += $awarded;
                if ($isCorrect) { $scoreCorrect++; $detail['correct']++; } else { $detail['incorrect']++; }
            }

            // Persist attempt summary (marks-based)
            $attempt->score = $detail['marks_total'];                           // score == marks
            $attempt->detail = $detail + ['correct_count' => $scoreCorrect];    // analytics
            $attempt->completed_at = now();
            $attempt->save();
        });

        return response()->json([
            'success' => true,
            'attempt' => $attempt->fresh()->load('attemptAnswers'),
        ]);
    }

    /**
     * Body-only variant: { "attempt_id": X, "responses": {...} }
     */
    public function finishByBody(Request $request)
    {
        $request->validate(['attempt_id' => 'required|integer']);
        $attempt = QuizAttempt::findOrFail($request->integer('attempt_id'));
        return $this->finish($request, $attempt);
    }

    /**
     * View attempt (student owner OR teacher)
     */
    public function show(Request $request, QuizAttempt $attempt)
    {
        $user = $request->user();
        $isTeacher = $user->tokenCan('teacher') || (($user->role ?? null) === 'teacher');

        if (!($isTeacher || $attempt->student_id === $user->id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'success' => true,
            'attempt' => $attempt->load(['quiz', 'attemptAnswers.question', 'attemptAnswers.answer']),
        ]);
    }

    /**
     * List pending short answers that require manual marking
     */
    public function pending(Request $request, QuizAttempt $attempt)
    {
        $user = $request->user();
        $isTeacher = $user->tokenCan('teacher') || (($user->role ?? null) === 'teacher');
        if (!($isTeacher || $attempt->student_id === $user->id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $items = AttemptAnswer::with(['question:id,quiz_id,text,type,scoring,marks'])
            ->where('attempt_id', $attempt->id)
            ->whereHas('question', function ($q) {
                $q->where('type', 'short')->where('scoring', 'manual');
            })
            ->where('marking_method', 'manual')
            ->get([
                'id',
                'question_id',
                'response_text',
                'is_correct',
                'awarded_marks',
                'marking_method',
                'created_at',
            ]);

        return response()->json([
            'success' => true,
            'attempt_id' => $attempt->id,
            'pending' => $items,
            'pending_count' => $items->count(),
        ]);
    }

    /**
     * Teacher marks short answers
     * PATCH /api/v1/attempts/{attempt}/mark
     */
    public function markShort(Request $request, QuizAttempt $attempt)
    {
        $user = $request->user();
        $isTeacher = $user->tokenCan('teacher') || (($user->role ?? null) === 'teacher');
        if (!$isTeacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'marks' => ['required', 'array', 'min:1'],
            'marks.*.question_id' => ['required', 'integer'],
            'marks.*.score' => ['required', 'numeric', 'min:0'],
            'marks.*.comment' => ['nullable', 'string'],
        ]);

        // Load questions to know max marks and types
        $attempt->load('quiz.questions');
        $questionsById = $attempt->quiz->questions->keyBy('id');

        DB::transaction(function () use ($attempt, $data, $questionsById) {
            foreach ($data['marks'] as $m) {
                $qid = (int) $m['question_id'];
                $q = $questionsById->get($qid);
                if (!$q || ($q->type ?? '') !== 'short' || ($q->scoring ?? 'manual') !== 'manual') {
                    // ignore unknown/non-short/non-manual questions
                    continue;
                }

                $maxMarks = (float) ($q->marks ?? 1.0);
                $awarded = max(0.0, min($maxMarks, (float) $m['score']));
                $isCorrect = ($awarded >= $maxMarks); // threshold: full marks => correct

                // Update the pending AttemptAnswer row for this short question
                $aa = AttemptAnswer::where('attempt_id', $attempt->id)
                    ->where('question_id', $qid)
                    ->where('marking_method', 'manual')
                    ->latest('id')
                    ->first();

                if ($aa) {
                    $aa->is_correct = $isCorrect ? 1 : 0;
                    $aa->awarded_marks = $awarded;
                    $aa->save();
                }
            }

            // Recompute attempt summary from DB
            $attempt->load(['quiz.questions.answers', 'attemptAnswers']);
            $questions = $attempt->quiz->questions;

            $detail = [
                'quiz_id' => $attempt->quiz_id,
                'items' => [],
                'total' => $questions->count(),
                'correct' => 0,
                'incorrect' => 0,
                'skipped' => 0,
                'marks_total' => 0.0,
            ];
            $scoreCorrect = 0;

            foreach ($questions as $q) {
                if (($q->type ?? '') === 'short' && ($q->scoring ?? 'manual') === 'manual') {
                    $aa = $attempt->attemptAnswers
                        ->where('question_id', $q->id)
                        ->where('marking_method', 'manual')
                        ->sortByDesc('id')->first();

                    $awarded = (float) ($aa->awarded_marks ?? 0);
                    $isCorrect = (int) ($aa->is_correct ?? 0) === 1;

                    $detail['items'][] = [
                        'question_id' => $q->id,
                        'given_answer' => $aa?->response_text,
                        'correct_answers' => [],
                        'is_correct' => $isCorrect,
                        'status' => $aa ? ($isCorrect ? 'correct' : 'incorrect') : 'pending',
                        'awarded_marks' => $awarded,
                    ];

                    if ($aa) {
                        if ($isCorrect) { $scoreCorrect++; $detail['correct']++; }
                        else { $detail['incorrect']++; }
                        $detail['marks_total'] += $awarded;
                    } else {
                        $detail['skipped']++;
                    }
                    continue;
                }

                // MCQ/TF
                $rows = $attempt->attemptAnswers->where('question_id', $q->id);
                $awarded = (float) ($rows->sortBy('id')->first()->awarded_marks ?? 0.0);
                $correctIds = $q->answers->where('is_correct', true)->pluck('id')->map(fn($x) => (string)$x)->values()->toArray();
                $givenIds = $rows->whereNotNull('answer_id')->pluck('answer_id')->map(fn($x) => (string)$x)->values()->toArray();

                $givenSorted = $givenIds; sort($givenSorted);
                $correctSorted = $correctIds; sort($correctSorted);
                $isCorrect = (!empty($givenSorted) && !empty($correctSorted) && $givenSorted === $correctSorted);

                $detail['items'][] = [
                    'question_id' => $q->id,
                    'given_answer' => $givenIds ?: null,
                    'correct_answers' => $correctIds,
                    'is_correct' => $isCorrect,
                    'status' => $isCorrect ? 'correct' : (empty($givenIds) ? 'skipped' : 'incorrect'),
                    'awarded_marks' => $awarded,
                ];

                if (empty($givenIds)) { $detail['skipped']++; }
                else {
                    if ($isCorrect) { $scoreCorrect++; $detail['correct']++; }
                    else { $detail['incorrect']++; }
                }
                $detail['marks_total'] += $awarded;
            }

            $attempt->score = $detail['marks_total'];
            $attempt->detail = $detail + ['correct_count' => $scoreCorrect];

            // graded_at if no pending manuals left
            $pending = AttemptAnswer::where('attempt_id', $attempt->id)
                ->where('marking_method', 'manual')
                ->whereNull('is_correct')->exists();
            if (!$pending) {
                $attempt->graded_at = now();
            }

            $attempt->save();
        });

        return response()->json([
            'success' => true,
            'attempt' => $attempt->fresh()->load(['attemptAnswers.question', 'attemptAnswers.answer']),
        ]);
    }
}
