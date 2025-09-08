<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
                Schema::table('answers', function (Blueprint $t) {
            // ✅ Drops the foreign key AND the column in one call (if it exists)
            if (Schema::hasColumn('answers', 'attempt_id')) {
                $t->dropConstrainedForeignId('attempt_id');
            }

            // Ensure answers keep the right columns for question options
            if (!Schema::hasColumn('answers', 'question_id')) {
                $t->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('answers', 'text')) {
                $t->string('text');
            }
            if (!Schema::hasColumn('answers', 'is_correct')) {
                $t->boolean('is_correct')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $t) {
            // If you ever rollback, you can re-add attempt_id (optional)
            if (!Schema::hasColumn('answers', 'attempt_id')) {
                $t->foreignId('attempt_id')->nullable()
                  ->constrained('quiz_attempts')->nullOnDelete();
            }
        });
    }
    };