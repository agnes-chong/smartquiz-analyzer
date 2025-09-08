<?php
// Author: Chong Pei Lee
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;

class QuestionApiController extends Controller
{
    /* ===========================
     | Helpers / wrappers
     ============================*/
    private function ok($data = null, string $message = 'OK', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    private function fail(string $message = 'Error', $errors = null, int $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }

    private function normalizeType(?string $raw): string
    {
        $raw = strtolower((string) $raw);

        $map = [
            'multiple_choice' => 'mcq',
            'checkbox'        => 'mcq',
            'mcq'             => 'mcq',
            'true_false'      => 'tf',
            'tf'              => 'tf',
            'short_answer'    => 'short',
            'short'           => 'short',
        ];

        return $map[$raw] ?? 'mcq';
    }

    private function authorizeQuiz(Request $request, Quiz $quiz)
    {
        $user = $request->user();
        if (! $user || (! $user->tokenCan('teacher') && ! $user->tokenCan('admin'))) {
            return $this->fail('Forbidden', null, 403);
        }
        if (! $user->tokenCan('admin') && (int)$quiz->teacher_id !== (int)$user->id) {
            return $this->fail('Forbidden (not your quiz)', null, 403);
        }
        return null; // ok
    }

    /* ===========================
     | Index: GET /api/v1/quizzes/{quiz}/questions
     | Hide answers for students/guests
     ============================*/
public function index(Quiz $quiz)
{
    // Eager load answers (only the fields we need)
    $rows = $quiz->questions()
        ->with(['answers:id,question_id,text,is_correct'])
        ->select('id','quiz_id','type','text','marks','scoring','penalty','data','answer','created_at','updated_at')
        ->latest('id')
        ->get();

    $user = request()->user();
    $isTeacher = $user && ($user->tokenCan('teacher') || $user->tokenCan('admin'));

    $data = $rows->map(function ($q) use ($isTeacher) {
        // build options from answers table so students get option IDs
        $opts = $q->answers->map(function ($a) use ($isTeacher) {
            return $isTeacher
                ? ['id' => $a->id, 'text' => $a->text, 'is_correct' => (bool)$a->is_correct] // teacher sees flags
                : ['id' => $a->id, 'text' => $a->text]; // student: no correctness
        })->values()->all();

        return [
            'id'        => $q->id,
            'quiz_id'   => $q->quiz_id,
            'type'      => $q->type,
            'prompt'    => $q->data['prompt'] ?? $q->text,
            'options'   => in_array($q->type, ['mcq','tf']) ? $opts : null, // short has no options
            'marks'     => $q->marks,
            'scoring'   => $q->scoring,
            'penalty'   => $q->penalty,
            'answer'    => $isTeacher ? $q->answer : null, // hide key from students
            'created_at'=> $q->created_at,
            'updated_at'=> $q->updated_at,
        ];
    });

    return $this->ok($data, 'Questions fetched');
}

    /* ===========================
     | Store: POST /api/v1/quizzes/{quiz}/questions
     | Sync MCQ/TF options into answers table
     ============================*/
    public function store(Request $request, Quiz $quiz)
    {
        if ($resp = $this->authorizeQuiz($request, $quiz)) return $resp;

        $type = $this->normalizeType($request->input('type'));

        // Base rules
        $rules = [
            'type'    => ['required', Rule::in(['mcq','tf','short'])],
            'prompt'  => ['required','string','max:2000'],
            'marks'   => ['required','numeric','min:0.1','max:1000'],
            'scoring' => ['sometimes', Rule::in(['exact','partial','negative','manual'])],
            'penalty' => ['sometimes','numeric','min:0','max:1000'],
        ];

        // Options & answer rules by type
        if (in_array($type, ['mcq','tf'])) {
            $rules['options']   = ['required','array','min:2'];
            $rules['options.*'] = ['string','max:255'];
            $rules['answer']    = ['required']; // mcq: array|int ; tf: int
        } else {
            $rules['answer']    = ['required','string','max:2000'];
        }

        $v = Validator::make($request->all(), $rules);

        // Semantic checks
        $v->after(function ($validator) use ($request, $type) {
            if (in_array($type, ['mcq','tf'])) {
                $opts = array_values((array)$request->input('options', []));
                foreach ($opts as $i => $opt) {
                    if (!is_string($opt) || trim($opt) === '') {
                        $validator->errors()->add("options.$i", 'Option must be a non-empty string.');
                    }
                }

                $ans = $request->input('answer');
                if ($type === 'mcq') {
                    $arr = is_array($ans) ? $ans : [$ans];
                    foreach ($arr as $a) {
                        if (!is_numeric($a)) {
                            $validator->errors()->add('answer', 'MCQ answer must be integer index(es).');
                        } elseif ((int)$a < 0 || (int)$a >= count($opts)) {
                            $validator->errors()->add('answer', 'Answer index out of range.');
                        }
                    }
                } else { // tf
                    if (!is_numeric($ans)) {
                        $validator->errors()->add('answer', 'TF answer must be integer index.');
                    } elseif ((int)$ans < 0 || (int)$ans >= count($opts)) {
                        $validator->errors()->add('answer', 'Answer index out of range.');
                    }
                }
            }
        });

        if ($v->fails()) {
            return $this->fail('Validation error', $v->errors(), 422);
        }

        $in = $v->validated();

        // Build payload for DB
        $payload = [
            'quiz_id' => $quiz->id,
            'type'    => $type,
            'text'    => $in['prompt'],
            'marks'   => (float) $in['marks'],
            'scoring' => $in['scoring'] ?? ($type === 'short' ? 'manual' : 'exact'),
            'penalty' => $in['penalty'] ?? null,
            'data'    => [
                'prompt'  => $in['prompt'],
                'options' => in_array($type, ['mcq','tf']) ? array_values($in['options']) : null,
            ],
        ];

        if ($type === 'mcq') {
            $ans = array_values((array)($in['answer']));
            $ans = array_map('intval', $ans);
            $payload['answer'] = $ans;                   // JSON (array of indices)
            $payload['correct_answer'] = implode(',', $ans); // optional legacy mirror
        } elseif ($type === 'tf') {
            $idx = (int)$in['answer'];
            $payload['answer'] = [$idx];
            $payload['correct_answer'] = (string)$idx;
        } else { // short
            $txt = (string)$in['answer'];
            $payload['answer'] = [$txt];
            $payload['correct_answer'] = $txt;
        }

        $question = null;

        DB::transaction(function () use (&$question, $payload, $type) {
            $question = Question::create($payload);

            // Sync answers table for MCQ/TF
            if (in_array($type, ['mcq','tf'])) {
                $opts = $payload['data']['options'] ?? [];
                $correctIdx = $type === 'mcq' ? $payload['answer'] : [$payload['answer'][0]];
                Answer::where('question_id', $question->id)->delete();
                foreach ($opts as $i => $label) {
                    Answer::create([
                        'question_id' => $question->id,
                        'text'        => $label,
                        'is_correct'  => in_array($i, $correctIdx, true),
                    ]);
                }
            }
        });

        $out = [
            'id'        => $question->id,
            'quiz_id'   => $question->quiz_id,
            'type'      => $question->type,
            'prompt'    => $question->data['prompt'] ?? $question->text,
            'options'   => $question->data['options'] ?? null,
            'marks'     => $question->marks,
            'scoring'   => $question->scoring,
            'penalty'   => $question->penalty,
            'answer'    => $question->answer, // array
            'created_at'=> $question->created_at,
        ];

        return $this->ok($out, 'Question created', 201);
    }

    /* ===========================
     | Update: PATCH /api/v1/questions/{question}
     | Re-sync answers if options/answer changed
     ============================*/
    public function update(Request $request, Question $question)
    {
        $quiz = $question->quiz;
        if ($resp = $this->authorizeQuiz($request, $quiz)) return $resp;

        $type = $this->normalizeType($request->input('type', $question->type));

        $rules = [
            'type'    => ['sometimes', Rule::in(['mcq','tf','short'])],
            'prompt'  => ['sometimes','string','max:2000'],
            'marks'   => ['sometimes','numeric','min:0.1','max:1000'],
            'scoring' => ['sometimes', Rule::in(['exact','partial','negative','manual'])],
            'penalty' => ['sometimes','numeric','min:0','max:1000'],
        ];

        if (in_array($type, ['mcq','tf'])) {
            if ($request->has('options')) {
                $rules['options']   = ['array','min:2'];
                $rules['options.*'] = ['string','max:255'];
            }
            if ($request->has('answer')) {
                $rules['answer']    = ['required'];
            }
        } else {
            if ($request->has('answer')) {
                $rules['answer']    = ['required','string','max:2000'];
            }
        }

        $v = Validator::make($request->all(), $rules);

        $v->after(function ($validator) use ($request, $type, $question) {
            $opts = array_values(
                $request->input('options', $question->data['options'] ?? [])
            );

            if ($request->has('options') && in_array($type, ['mcq','tf'])) {
                foreach ($opts as $i => $opt) {
                    if (!is_string($opt) || trim($opt) === '') {
                        $validator->errors()->add("options.$i", 'Option must be a non-empty string.');
                    }
                }
            }

            if ($request->has('answer')) {
                $ans = $request->input('answer');
                if ($type === 'mcq') {
                    $arr = is_array($ans) ? $ans : [$ans];
                    foreach ($arr as $a) {
                        if (!is_numeric($a)) {
                            $validator->errors()->add('answer', 'MCQ answer must be integer index(es).');
                        } elseif ($opts && ((int)$a < 0 || (int)$a >= count($opts))) {
                            $validator->errors()->add('answer', 'Answer index out of range.');
                        }
                    }
                } elseif ($type === 'tf') {
                    if (!is_numeric($ans)) {
                        $validator->errors()->add('answer', 'TF answer must be integer index.');
                    } elseif ($opts && ((int)$ans < 0 || (int)$ans >= count($opts))) {
                        $validator->errors()->add('answer', 'Answer index out of range.');
                    }
                } else {
                    if (!is_string($ans)) {
                        $validator->errors()->add('answer', 'Short answer must be a string.');
                    }
                }
            }
        });

        if ($v->fails()) {
            return $this->fail('Validation error', $v->errors(), 422);
        }

        $in  = $v->validated();
        $upd = [];

        if (array_key_exists('type', $in))   $upd['type']   = $type;
        if (array_key_exists('prompt', $in)) $upd['text']   = $in['prompt'];
        if (array_key_exists('marks', $in))  $upd['marks']  = (float)$in['marks'];
        if (array_key_exists('scoring', $in))$upd['scoring']= $in['scoring'];
        if (array_key_exists('penalty', $in))$upd['penalty']= $in['penalty'];

        // data (prompt + options)
        $data = $question->data ?? [];
        if (array_key_exists('prompt', $in))           $data['prompt']  = $in['prompt'];
        if (array_key_exists('options', $in))          $data['options'] = in_array($type, ['mcq','tf']) ? array_values($in['options']) : null;
        if (!empty($data))                              $upd['data']     = $data;

        // answer + correct_answer
        if (array_key_exists('answer', $in)) {
            if ($type === 'mcq') {
                $ans = array_values((array)$in['answer']);
                $ans = array_map('intval', $ans);
                $upd['answer']          = $ans;
                $upd['correct_answer']  = implode(',', $ans);
            } elseif ($type === 'tf') {
                $idx = (int)$in['answer'];
                $upd['answer']          = [$idx];
                $upd['correct_answer']  = (string)$idx;
            } else {
                $txt = (string)$in['answer'];
                $upd['answer']          = [$txt];
                $upd['correct_answer']  = $txt;
            }
        }

        DB::transaction(function () use ($question, $upd, $type, $data) {
            $question->update($upd);

            // Re-sync answers for MCQ/TF if options/answer changed
            if (in_array($type, ['mcq','tf'])) {
                $opts = $data['options'] ?? ($question->data['options'] ?? []);
                $correctIdx = $type === 'mcq'
                    ? ($upd['answer'] ?? $question->answer ?? [])
                    : [($upd['answer'][0] ?? $question->answer[0] ?? 0)];

                Answer::where('question_id', $question->id)->delete();
                foreach ($opts as $i => $label) {
                    Answer::create([
                        'question_id' => $question->id,
                        'text'        => $label,
                        'is_correct'  => in_array($i, $correctIdx, true),
                    ]);
                }
            }
        });

        $out = [
            'id'        => $question->id,
            'quiz_id'   => $question->quiz_id,
            'type'      => $question->type,
            'prompt'    => $question->data['prompt'] ?? $question->text,
            'options'   => $question->data['options'] ?? null,
            'marks'     => $question->marks,
            'scoring'   => $question->scoring,
            'penalty'   => $question->penalty,
            'answer'    => $question->answer,
            'updated_at'=> $question->updated_at,
        ];

        return $this->ok($out, 'Question updated');
    }

    /* ===========================
     | Destroy
     | DELETE /api/v1/questions/{question}
     ============================*/
    public function destroy(Request $request, Question $question)
    {
        $quiz = $question->quiz;
        if ($resp = $this->authorizeQuiz($request, $quiz)) return $resp;

        $question->delete();
        return $this->ok(null, 'Question deleted');
    }
}
