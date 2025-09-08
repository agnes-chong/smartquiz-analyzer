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
        Question::with('answers')->chunk(200, function ($questions) {
            foreach ($questions as $q) {
                $data = is_array($q->data) ? $q->data : (json_decode((string)$q->data, true) ?: []);
                $options = Arr::get($data, 'options');

                // Short: ensure no dangling options
                if (($q->type ?? '') === 'short') {
                    Answer::where('question_id', $q->id)->delete();
                    continue;
                }

                if (!is_array($options) || !count($options)) {
                    // No options known -> do NOT overwrite existing correctness
                    // (just skip; leave existing answers as-is)
                    continue;
                }

                // Correct indices from question->answer or correct_answer
                $correctIdx = $this->resolveCorrectIndices($q);

                DB::transaction(function () use ($q, $options, $correctIdx) {
                    $keptTexts = [];
                    foreach ($options as $i => $label) {
                        $text = is_scalar($label) ? (string)$label : json_encode($label);
                        $keptTexts[] = $text;

                        // Upsert by (question_id,text). Only set is_correct if we know the key.
                        $attrs = ['question_id' => $q->id, 'text' => $text];
                        $vals  = [];
                        if (!empty($correctIdx)) {
                            $vals['is_correct'] = in_array((int)$i, $correctIdx, true);
                        }
                        $row = Answer::updateOrCreate($attrs, $vals);

                        // If we didn't know the key and row didn't exist, default false
                        if (empty($correctIdx) && !$row->wasRecentlyCreated) {
                            // keep existing is_correct as-is
                        } elseif (empty($correctIdx) && $row->wasRecentlyCreated) {
                            $row->is_correct = false;
                            $row->save();
                        }
                    }

                    // Remove stale rows (texts no longer present)
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
        return []; // unknown -> do not override existing flags
    }

    private function allNumeric(array $arr): bool
    {
        foreach ($arr as $v) {
            if (!(is_int($v) || (is_string($v) && ctype_digit($v)))) return false;
        }
        return true;
    }
}
