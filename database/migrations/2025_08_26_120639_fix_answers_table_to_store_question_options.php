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
        Schema::table('answers', function (Blueprint $table) {
            // If these columns exist, drop them (table is empty so it's safe)
            if (Schema::hasColumn('answers', 'attempt_id')) {
                $table->dropColumn('attempt_id');
            }
            if (Schema::hasColumn('answers', 'response')) {
                $table->dropColumn('response');
            }

            // Add the option text column if missing
            if (!Schema::hasColumn('answers', 'text')) {
                $table->string('text');
            }

            // Ensure FK to questions is correct type & constraint
            // (If your questions.id is BIGINT auto-increment, this is fine)
            // If questions.id is UUID, tell me and we’ll adjust.
            if (!Schema::hasColumn('answers', 'question_id')) {
                $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            } else {
                // (Optional) add FK if not present
                // You may need to drop an existing foreign key first if it differs.
                // $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
            }

            // Make sure is_correct exists (boolean)
            if (!Schema::hasColumn('answers', 'is_correct')) {
                $table->boolean('is_correct')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            // revert if you want (optional)
            if (Schema::hasColumn('answers', 'text')) {
                $table->dropColumn('text');
            }
            if (!Schema::hasColumn('answers', 'response')) {
                $table->text('response')->nullable();
            }
            if (!Schema::hasColumn('answers', 'attempt_id')) {
                $table->unsignedBigInteger('attempt_id')->nullable();
            }
        });
    }
    };