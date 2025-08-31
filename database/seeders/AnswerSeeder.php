<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Models\Question;
use App\Models\Answer;

class AnswerSeeder extends Seeder
{
    public function run(): void
    {
        Question::chunk(200, function ($questions) {
            foreach ($questions as $q) {
                $data = $q->data;
                if (is_string($data)) {
                    $data = json_decode($data, true) ?: [];
                }
                $options = Arr::get($data, 'options');

                // short → no options, ensure none dangling
                if (($q->type ?? '') === 'short') {
                    Answer::where('question_id', $q->id)->delete();
                    continue;
                }

                // tf fallback
                if (($q->type ?? '') === 'tf' && empty($options)) {
                    $options = ['False', 'True'];
                }
                if (!is_array($options) || !count($options)) {
                    Answer::where('question_id', $q->id)->delete();
                    continue;
                }

                $correctIdx = $this->resolveCorrectIndices($q);

                DB::transaction(function () use ($q, $options, $correctIdx) {
                    // Upsert each option by (question_id, text)
                    $keptTexts = [];
                    foreach ($options as $i => $label) {
                        $text = is_scalar($label) ? (string)$label : json_encode($label);
                        $keptTexts[] = $text;

                        Answer::updateOrCreate(
                            ['question_id' => $q->id, 'text' => $text],
                            ['is_correct'  => in_array((int)$i, $correctIdx, true)]
                        );
                    }

                    // Delete stale rows (texts no longer present)
                    Answer::where('question_id', $q->id)
                          ->whereNotIn('text', $keptTexts)
                          ->delete();
                });
            }
        });
    }

    private function resolveCorrectIndices($q): array
    {
        $ans = $q->answer;
        if (is_string($ans)) {
            $d = json_decode($ans, true);
            if (is_array($d)) $ans = $d;
        }
        if (is_array($ans) && $this->allNumeric($ans)) {
            return array_map('intval', $ans);
        }
        $ca = $q->correct_answer;
        if (is_string($ca) && trim($ca) !== '') {
            return array_map('intval',
                array_filter(array_map('trim', explode(',', $ca)), fn($s) => $s !== '')
            );
        }
        return [];
    }

    private function allNumeric(array $arr): bool
    {
        foreach ($arr as $v) {
            if (!(is_int($v) || (is_string($v) && ctype_digit($v)))) return false;
        }
        return true;
    }
}
