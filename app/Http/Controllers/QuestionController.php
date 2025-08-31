<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        // optional filters: ?quiz_id=1
        $q = Question::query()->with('quiz');
        if ($request->filled('quiz_id')) {
            $q->where('quiz_id', $request->integer('quiz_id'));
        }
        return response()->json($q->latest()->paginate(20));
    }

    public function create()
    {
        // not used (API style). If you render Blade, return a view here.
        abort(404);
    }

    public function store(Request $request)
    {
        // normalize type aliases from your older code
        $type = $this->normalizeType($request->input('type'));

        $validated = $request->validate([
            'quiz_id'        => ['required', Rule::exists('quizzes', 'id')],
            'text'           => ['required','string','max:2000'],          // prompt
            'type'           => ['required', Rule::in(['mcq','tf','short'])],

            // Strategy-related
            'scoring'        => ['required', Rule::in(['exact','partial','negative'])],
            'marks'          => ['required','numeric','min:0'],
            'penalty'        => ['nullable','numeric','min:0'],

            // Answers & options
            // MCQ: options required, answer can be array (multi) or string (single)
            'options'        => [$type === 'mcq' ? 'required' : 'nullable', 'array', 'min:2'],
            'options.*'      => ['string','max:255'],

            // For TF/Short, a single string; for MCQ you may send array or string (we’ll normalize)
            'correct_answer' => [$type === 'mcq' ? 'required' : 'required','present'],
        ]);

        // Build payload consistent with Strategy-based schema
        // data = prompt + options, answer = canonical answer(s)
        $payload = [
            'quiz_id' => (int)$validated['quiz_id'],
            'type'    => $type,                                   // 'mcq' | 'tf' | 'short'
            'scoring' => $validated['scoring'],                   // 'exact' | 'partial' | 'negative'
            'marks'   => (float)$validated['marks'],
            'penalty' => $validated['penalty'] ?? null,
            'data'    => [
                'prompt'  => $validated['text'],
                'options' => $type === 'mcq' ? array_values($validated['options']) : null,
            ],
            // normalize answer:
            'answer'  => $this->normalizeAnswer($type, $validated['correct_answer']),
        ];

        $question = Question::create($payload);

        return response()->json($question, 201);
    }

    public function show(Question $question)
    {
        return response()->json($question->load('quiz'));
    }

    public function edit(Question $question)
    {
        // not used (API style). If you render Blade, return a view here.
        abort(404);
    }

    public function update(Request $request, Question $question)
    {
        $type = $this->normalizeType($request->input('type', $question->type));

        $validated = $request->validate([
            'text'           => ['sometimes','required','string','max:2000'],
            'type'           => ['sometimes','required', Rule::in(['mcq','tf','short'])],
            'scoring'        => ['sometimes','required', Rule::in(['exact','partial','negative'])],
            'marks'          => ['sometimes','required','numeric','min:0'],
            'penalty'        => ['nullable','numeric','min:0'],
            'options'        => [$type === 'mcq' ? 'sometimes' : 'nullable','array','min:2'],
            'options.*'      => ['string','max:255'],
            'correct_answer' => ['sometimes','present'],
        ]);

        $data = $question->data ?? [];
        if (array_key_exists('text', $validated)) {
            $data['prompt'] = $validated['text'];
        }
        if (array_key_exists('options', $validated)) {
            $data['options'] = $type === 'mcq' ? array_values($validated['options']) : null;
        }

        $updates = [];
        foreach (['type','scoring','marks','penalty'] as $fld) {
            if (array_key_exists($fld, $validated)) $updates[$fld] = $validated[$fld];
        }
        if (!empty($data)) $updates['data'] = $data;

        if (array_key_exists('correct_answer', $validated)) {
            $updates['answer'] = $this->normalizeAnswer($type, $validated['correct_answer']);
        }

        $question->update($updates);

        return response()->json($question->fresh());
    }

    public function destroy(Question $question)
    {
        $question->delete();
        return response()->json(['deleted' => true]);
    }

    // ----------------- helpers -----------------

    private function normalizeType(?string $raw): string
    {
        $map = [
            'multiple_choice' => 'mcq',
            'mcq'             => 'mcq',
            'true_false'      => 'tf',
            'tf'              => 'tf',
            'short_answer'    => 'short',
            'short'           => 'short',
        ];
        return $map[strtolower((string)$raw)] ?? 'mcq';
    }

    private function normalizeAnswer(string $type, $input)
    {
        // Accept comma-separated string or array for MCQ; cast to string for TF/Short
        if ($type === 'mcq') {
            if (is_string($input)) {
                // allow "A,C" or "A;C"
                $parts = preg_split('/[;,]+/', $input);
                return array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));
            }
            return array_values((array) $input);
        }

        // TF/Short => single canonical string
        return is_array($input) ? (string)reset($input) : (string)$input;
    }
}
