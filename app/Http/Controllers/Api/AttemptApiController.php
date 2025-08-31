<?php
// Author: Chong Pei Lee
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\AttemptAnswer;

class AttemptApiController extends Controller
{
    /**
     * Start a new attempt (student)
     */
    public function store(Request $request, int $quizId)
    {
        $quiz = Quiz::findOrFail($quizId);

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $request->user()->id,
            'score' => 0,
            'detail' => null,
            'completed_at' => null,
        ]);

        return response()->json(['success' => true, 'attempt' => $attempt], 201);
    }

    /**
     * Submit/finish an attempt (owner only).
     * Route-model binding passes the QuizAttempt.
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

        // Decode "responses"
        $raw = $request->input('responses');
        if ($raw === null) {
            throw ValidationException::withMessages([
                'responses' => ['The responses field is required.'],
            ]);
        }

        if (is_string($raw)) {
            try {
                $responses = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw ValidationException::withMessages([
                    'responses' => ['Invalid JSON in responses: ' . $e->getMessage()],
                ]);
            }
        } elseif (is_array($raw)) {
            $responses = $raw;
        } else {
            throw ValidationException::withMessages([
                'responses' => ['Responses must be an object/array.'],
            ]);
        }

        $quiz = Quiz::with(['questions.answers'])->findOrFail($attempt->quiz_id);
        $questions = $quiz->questions;

        $detail = [
            'quiz_id' => $quiz->id,
            'items' => [],
            'total' => $questions->count(),
            'correct' => 0,
            'incorrect' => 0,
            'skipped' => 0,
            'marks_total' => 0.0,
        ];

        $scoreCorrectQuestions = 0;

        DB::transaction(function () use ($attempt, $questions, $responses, &$detail, &$scoreCorrectQuestions) {

            foreach ($questions as $q) {
                $qid = (string) $q->id;
                $given = $responses[$qid] ?? null;
                $qMarks = (float) ($q->marks ?? 1.0);

                // Normalize "given" into array-of-ids for MCQ/multi; string for short
                $givenIds = is_array($given) ? array_map('strval', $given)
                    : ((is_null($given) || $given === '') ? [] : [(string) $given]);

                /**
                 * SHORT ANSWER
                 */
                if (($q->type ?? '') === 'short') {
                    $text = is_string($given) ? trim($given) : null;
                    $isManual = (($q->scoring ?? 'auto') === 'manual');

                    if ($isManual) {
                        // Save as pending for manual marking
                        AttemptAnswer::create([
                            'attempt_id' => $attempt->id,
                            'question_id' => $q->id,
                            'answer_id' => null,
                            'response_text' => $text,
                            'is_correct' => 0,             // use 0 if column NOT NULL
                            'awarded_marks' => 0,
                            'marking_method' => 'manual',
                        ]);

                        $detail['items'][] = [
                            'question_id' => $q->id,
                            'given_answer' => $text,
                            'correct_answers' => [],
                            'is_correct' => false,
                            'status' => 'pending',
                            'awarded_marks' => 0,
                        ];

                        // Track as pending; we count it in "skipped" bucket for now
                        $detail['skipped']++;
                        continue;
                    }

                    // Auto-graded short
                    $canonical = collect((array) ($q->answer ?? []))
                        ->map(fn($s) => is_string($s) ? mb_strtolower(trim($s)) : '')
                        ->filter()->values()->all();

                    $isCorrect = $text !== null && in_array(mb_strtolower($text), $canonical, true);
                    $awarded = $isCorrect ? $qMarks : 0.0;

                    AttemptAnswer::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $q->id,
                        'answer_id' => null,
                        'response_text' => $text,
                        'is_correct' => $isCorrect,
                        'awarded_marks' => $awarded,
                        'marking_method' => 'auto',
                    ]);

                    $detail['items'][] = [
                        'question_id' => $q->id,
                        'given_answer' => $text,
                        'correct_answers' => $canonical,
                        'is_correct' => $isCorrect,
                        'status' => $isCorrect ? 'correct' : 'incorrect',
                        'awarded_marks' => $awarded,
                    ];

                    if ($isCorrect) {
                        $scoreCorrectQuestions++;
                        $detail['correct']++;
                    } else {
                        $detail['incorrect']++;
                    }
                    $detail['marks_total'] += $awarded;
                    continue;
                }

                /**
                 * MCQ / MULTI-SELECT / TRUE-FALSE
                 */
                // Ensure provided ids belong to this question
                $validIds = $q->answers->pluck('id')->map(fn($x) => (string) $x)->all();
                $givenIds = array_values(array_filter($givenIds, fn($aid) => in_array($aid, $validIds, true)));

                $correctIds = $q->answers->where('is_correct', true)
                    ->pluck('id')->map(fn($id) => (string) $id)->values()->toArray();

                $hasGiven = count($givenIds) > 0;
                $hasKey = count($correctIds) > 0;

                // Nothing selected -> skipped
                if (!$hasGiven) {
                    AttemptAnswer::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $q->id,
                        'answer_id' => null,
                        'response_text' => null,
                        'is_correct' => false,
                        'awarded_marks' => 0,
                        'marking_method' => 'auto',
                    ]);

                    $detail['items'][] = [
                        'question_id' => $q->id,
                        'given_answer' => null,
                        'correct_answers' => $correctIds,
                        'is_correct' => false,
                        'status' => 'skipped',
                        'awarded_marks' => 0,
                    ];
                    $detail['skipped']++;
                    continue;
                }

                // No key configured -> invalid, don't award marks
                if (!$hasKey) {
                    $detail['items'][] = [
                        'question_id' => $q->id,
                        'given_answer' => $givenIds,
                        'correct_answers' => $correctIds,
                        'is_correct' => false,
                        'status' => 'invalid_key',
                        'awarded_marks' => 0,
                    ];
                    $detail['incorrect']++;
                    continue;
                }

                // Create rows per selected answer
                $rows = [];
                foreach ($givenIds as $aid) {
                    $ok = in_array($aid, $correctIds, true);
                    $rows[] = AttemptAnswer::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $q->id,
                        'answer_id' => (int) $aid,
                        'response_text' => null,
                        'is_correct' => $ok,
                        'awarded_marks' => 0, // set below
                        'marking_method' => 'auto',
                    ]);
                }

                // Exact match by set equality (now both non-empty)
                $givenSorted = $givenIds;
                sort($givenSorted);
                $correctSorted = $correctIds;
                sort($correctSorted);
                $isCorrect = ($givenSorted === $correctSorted);

                // Partial scoring support
                if (($q->scoring ?? 'exact') === 'partial') {
                    $intersection = count(array_intersect($givenIds, $correctIds));
                    $awarded = $intersection > 0
                        ? ($intersection / count($correctIds)) * $qMarks
                        : 0.0;
                } else {
                    $awarded = $isCorrect ? $qMarks : 0.0;
                }

                if (!empty($rows)) {
                    $first = $rows[0];
                    $first->awarded_marks = $awarded;
                    $first->save();
                }

                $detail['items'][] = [
                    'question_id' => $q->id,
                    'given_answer' => $givenIds,
                    'correct_answers' => $correctIds,
                    'is_correct' => $isCorrect,
                    'status' => $isCorrect ? 'correct' : 'incorrect',
                    'awarded_marks' => $awarded,
                ];

                if ($isCorrect) {
                    $scoreCorrectQuestions++;
                    $detail['correct']++;
                } else {
                    $detail['incorrect']++;
                }
                $detail['marks_total'] += $awarded;
            }

            // Persist attempt summary
            $attempt->score = $scoreCorrectQuestions; // number of correct questions
            $attempt->detail = $detail;
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
                $isCorrect = ($awarded >= $maxMarks); // define your threshold; here: full marks => correct

                // Update the pending AttemptAnswer row for this short question
                $aa = AttemptAnswer::where('attempt_id', $attempt->id)
                    ->where('question_id', $qid)
                    ->where('marking_method', 'manual')
                    ->latest('id')
                    ->first();

                if ($aa) {
                    $aa->is_correct = $isCorrect ? 1 : 0;
                    $aa->awarded_marks = $awarded;
                    // (optional) store comment if you have a column; otherwise skip
                    $aa->save();
                }
            }

            // Recompute attempt summary from DB (safer than trying to patch in place)
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
                        'correct_answers' => [],             // manual — no canonical list in detail
                        'is_correct' => $isCorrect,
                        'status' => $aa ? ($isCorrect ? 'correct' : 'incorrect') : 'pending',
                        'awarded_marks' => $awarded,
                    ];

                    if ($aa) {
                        if ($isCorrect) {
                            $scoreCorrect++;
                            $detail['correct']++;
                        } else {
                            $detail['incorrect']++;
                        }
                        $detail['marks_total'] += $awarded;
                    } else {
                        $detail['skipped']++;
                    }
                    continue;
                }

                // MCQ/TF: reuse the rows produced during finish()
                $rows = $attempt->attemptAnswers->where('question_id', $q->id);
                $awarded = (float) ($rows->sortBy('id')->first()->awarded_marks ?? 0.0);
                $correctIds = $q->answers->where('is_correct', true)->pluck('id')->map(fn($x) => (string) $x)->values()->toArray();
                $givenIds = $rows->whereNotNull('answer_id')->pluck('answer_id')->map(fn($x) => (string) $x)->values()->toArray();

                $givenSorted = $givenIds;
                sort($givenSorted);
                $correctSorted = $correctIds;
                sort($correctSorted);
                $isCorrect = (!empty($givenSorted) && !empty($correctSorted) && $givenSorted === $correctSorted);

                $detail['items'][] = [
                    'question_id' => $q->id,
                    'given_answer' => $givenIds ?: null,
                    'correct_answers' => $correctIds,
                    'is_correct' => $isCorrect,
                    'status' => $isCorrect ? 'correct' : (empty($givenIds) ? 'skipped' : 'incorrect'),
                    'awarded_marks' => $awarded,
                ];

                if (empty($givenIds)) {
                    $detail['skipped']++;
                } elseif ($isCorrect) {
                    $scoreCorrect++;
                    $detail['correct']++;
                } else {
                    $detail['incorrect']++;
                }
                $detail['marks_total'] += $awarded;
            }

            $attempt->score = $scoreCorrect;
            $attempt->detail = $detail;
            $attempt->save();
        });

        return response()->json([
            'success' => true,
            'attempt' => $attempt->fresh()->load(['attemptAnswers.question', 'attemptAnswers.answer']),
        ]);
    }

}
