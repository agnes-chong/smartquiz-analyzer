<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /* -------------------------------------------
         * 1) answers: drop FK on attempt_id (if any) and drop column
         * -------------------------------------------*/
        if (Schema::hasTable('answers') && Schema::hasColumn('answers', 'attempt_id')) {
            // Find the actual FK name (if any) tied to answers.attempt_id
            $fk = DB::selectOne("
                SELECT CONSTRAINT_NAME AS name
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'answers'
                  AND COLUMN_NAME = 'attempt_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            ");
            if ($fk && isset($fk->name)) {
                DB::statement("ALTER TABLE `answers` DROP FOREIGN KEY `{$fk->name}`");
            }
            // Drop the column now that FK (if any) is gone
            DB::statement("ALTER TABLE `answers` DROP COLUMN `attempt_id`");
        }

        /* -------------------------------------------
         * 2) attempt_answers: make is_correct/awarded_marks nullable, set marking_method default
         *    (raw SQL avoids requiring doctrine/dbal for change())
         * -------------------------------------------*/
        if (Schema::hasTable('attempt_answers')) {
            // is_correct -> nullable tinyint(1)
            try {
                DB::statement("ALTER TABLE `attempt_answers` MODIFY `is_correct` TINYINT(1) NULL");
            } catch (\Throwable $e) { /* ignore if already nullable */ }

            // awarded_marks -> nullable decimal(8,2)
            try {
                DB::statement("ALTER TABLE `attempt_answers` MODIFY `awarded_marks` DECIMAL(8,2) NULL");
            } catch (\Throwable $e) { /* ignore */ }

            // marking_method -> default 'auto'
            try {
                DB::statement("ALTER TABLE `attempt_answers` MODIFY `marking_method` VARCHAR(10) NOT NULL DEFAULT 'auto'");
            } catch (\Throwable $e) { /* ignore */ }

            // Ensure FKs (idempotent: add only if missing)
            $this->ensureForeignKey('attempt_answers', 'attempt_id', 'quiz_attempts', 'id', 'attempt_answers_attempt_id_foreign');
            $this->ensureForeignKey('attempt_answers', 'question_id', 'questions', 'id', 'attempt_answers_question_id_foreign');

            // answer_id is nullable FK to answers (nullOnDelete)
            if (Schema::hasColumn('attempt_answers','answer_id')) {
                // find if FK exists; if not, add it
                $fk = DB::selectOne("
                    SELECT CONSTRAINT_NAME AS name
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'attempt_answers'
                      AND COLUMN_NAME = 'answer_id'
                      AND REFERENCED_TABLE_NAME = 'answers'
                    LIMIT 1
                ");
                if (!$fk) {
                    // make sure column is nullable
                    try {
                        DB::statement("ALTER TABLE `attempt_answers` MODIFY `answer_id` BIGINT UNSIGNED NULL");
                    } catch (\Throwable $e) { /* ignore */ }
                    DB::statement("
                        ALTER TABLE `attempt_answers`
                        ADD CONSTRAINT `attempt_answers_answer_id_foreign`
                        FOREIGN KEY (`answer_id`) REFERENCES `answers`(`id`)
                        ON DELETE SET NULL
                    ");
                }
            }

            // Unique index on (attempt_id, question_id, answer_id) if missing
            $hasIdx = DB::selectOne("
                SELECT 1 FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'attempt_answers'
                  AND INDEX_NAME = 'attempt_question_answer_unique'
                LIMIT 1
            ");
            if (!$hasIdx) {
                DB::statement("
                    CREATE UNIQUE INDEX `attempt_question_answer_unique`
                    ON `attempt_answers` (`attempt_id`,`question_id`,`answer_id`)
                ");
            }
        }

        /* -------------------------------------------
         * 3) quiz_attempts: add started_at, graded_at if missing
         * -------------------------------------------*/
        if (Schema::hasTable('quiz_attempts')) {
            if (!Schema::hasColumn('quiz_attempts','started_at')) {
                Schema::table('quiz_attempts', function (Blueprint $t) {
                    $t->timestamp('started_at')->nullable()->after('detail');
                });
            }
            if (!Schema::hasColumn('quiz_attempts','graded_at')) {
                Schema::table('quiz_attempts', function (Blueprint $t) {
                    $t->timestamp('graded_at')->nullable()->after('completed_at');
                });
            }
        }
    }

    public function down(): void
    {
        // Best-effort rollback (non-destructive)
        if (Schema::hasTable('quiz_attempts')) {
            if (Schema::hasColumn('quiz_attempts','started_at')) {
                Schema::table('quiz_attempts', fn (Blueprint $t) => $t->dropColumn('started_at'));
            }
            if (Schema::hasColumn('quiz_attempts','graded_at')) {
                Schema::table('quiz_attempts', fn (Blueprint $t) => $t->dropColumn('graded_at'));
            }
        }

        if (Schema::hasTable('attempt_answers')) {
            // Drop unique index if exists
            $hasIdx = DB::selectOne("
                SELECT 1 FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'attempt_answers'
                  AND INDEX_NAME = 'attempt_question_answer_unique'
                LIMIT 1
            ");
            if ($hasIdx) {
                DB::statement("DROP INDEX `attempt_question_answer_unique` ON `attempt_answers`");
            }
        }

        // Do not recreate answers.attempt_id on down() – keep it simple/safe.
    }

    /**
     * Ensure a simple FK exists; if not, create it with ON DELETE CASCADE.
     */
    private function ensureForeignKey(string $table, string $column, string $refTable, string $refColumn, string $expectedName): void
    {
        if (!Schema::hasColumn($table, $column)) return;

        $existing = DB::selectOne("
            SELECT CONSTRAINT_NAME AS name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME = ?
            LIMIT 1
        ", [$table, $column, $refTable]);

        if (!$existing) {
            DB::statement("
                ALTER TABLE `{$table}`
                ADD CONSTRAINT `{$expectedName}`
                FOREIGN KEY (`{$column}`) REFERENCES `{$refTable}`(`{$refColumn}`)
                ON DELETE CASCADE
            ");
        }
    }
};
