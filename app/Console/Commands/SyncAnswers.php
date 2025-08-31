<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Models\Question;
use App\Models\Answer;

class SyncAnswers extends Command
{
    protected $signature = 'quiz:sync-answers {--quiz_id=} {--dry}';
    protected $description = 'Upsert answers from Question->data[options], marking correct choices from Question->answer/correct_answer';

    public function handle(): int
    {
        $quizId = $this->option('quiz_id');
        $dry    = (bool) $this->option('dry');

        $this->info('Syncing answers ' . ($quizId ? "for quiz_id={$quizId}" : 'for ALL quizzes') . ($dry ? ' (dry run)' : '') . ' ...');

        $qBuilder = Question::query();
        if ($quizId) {
            $qBuilder->where('quiz_id', $quizId);
        }

        $count = 0;

        $qBuilder->orderBy('quiz_id')->orderBy('id')->chunk(200, function ($questions) use (&$count, $dry) {
            foreach ($questions as $q) {
                $count++;

                // Normalize data
                $data = $q->data;
                if (is_string($data)) {
                    $decoded = json_decode($data, true);
                    if (is_array($decoded)) $data = $decoded;
                }
                if (!is_array($data)) $data = [];

                $options = Arr::get($data, 'options');

                // Skip short-answer questions
                if (($q->type ?? '') === 'short') {
                    $this->line(" - Q{$q->id} [short] -> clearing any legacy answers");
                    if (!$dry) {
                        Answer::where('question_id', $q->id)->delete();
                    }
                    continue;
                }

                // TF fallback
                if (($q->type ?? '') === 'tf' && empty($options)) {
                    $options = ['False', 'True'];
                }

                if (!is_array($options) || count($options) === 0) {
                    $this->warn(" - Q{$q->id} [{$q->type}] has no options; clearing existing");
                    if (!$dry) {
                        Answer::where('question_id', $q->id)->delete();
                    }
                    continue;
                }

                // Determine correct option indices
                $correctIndices = $this->resolveCorrectIndices($q);

                $this->line(" - Q{$q->id} [{$q->type}] options=" . count($options) . " correct=" . json_encode($correctIndices));

                if ($dry) {
                    // preview
                    $preview = [];
                    foreach ($options as $i => $label) {
                        $preview[] = [
                            'text'       => is_scalar($label) ? (string)$label : json_encode($label),
                            'is_correct' => in_array((int)$i, $correctIndices, true),
                        ];
                        if (count($preview) >= 3) break;
                    }
                    $this->line('   preview: ' . json_encode($preview));
                    continue;
                }

                // Upsert rows atomically (keeps IDs stable if text unchanged)
                DB::transaction(function () use ($q, $options, $correctIndices) {
                    $keptTexts = [];

                    foreach ($options as $i => $label) {
                        $text = is_scalar($label) ? (string)$label : json_encode($label);
                        $keptTexts[] = $text;

                        Answer::updateOrCreate(
                            ['question_id' => $q->id, 'text' => $text],
                            ['is_correct'  => in_array((int)$i, $correctIndices, true)]
                        );
                    }

                    // Delete stale options not in question->data
                    Answer::where('question_id', $q->id)
                          ->whereNotIn('text', $keptTexts)
                          ->delete();
                });
            } // end foreach
        }); // end chunk callback

        $this->info("Done. Processed {$count} question(s).");
        return Command::SUCCESS;
    }

    /** Turn Question->answer / correct_answer into an array of integer indices */
    private function resolveCorrectIndices(Question $q): array
    {
        $ans = $q->answer;

        if (is_string($ans)) {
            $decoded = json_decode($ans, true);
            if (is_array($decoded)) $ans = $decoded;
        }

        if (is_array($ans) && $this->isArrayOfNumericStringsOrInts($ans)) {
            return array_map(fn($x) => (int)$x, $ans);
        }

        $ca = $q->correct_answer;
        if (is_string($ca) && strlen(trim($ca))) {
            return array_map('intval',
                array_filter(array_map('trim', explode(',', $ca)), fn($s) => $s !== '')
            );
        }

        if (($q->type ?? '') === 'tf' && is_array($ans)) {
            $nums = array_values(array_filter(array_map(function ($x) {
                if (is_int($x)) return $x;
                if (is_string($x) && ctype_digit($x)) return (int)$x;
                return null;
            }, $ans), fn($v) => $v !== null));
            return $nums;
        }

        return [];
    }

    private function isArrayOfNumericStringsOrInts(array $arr): bool
    {
        foreach ($arr as $v) {
            if (!(is_int($v) || (is_string($v) && ctype_digit((string)$v)))) {
                return false;
            }
        }
        return true;
    }
}