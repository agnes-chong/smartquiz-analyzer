<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignId('answer_id')->nullable()->constrained('answers')->nullOnDelete();

            $table->text('response_text')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->decimal('awarded_marks', 6, 2)->default(0);
            $table->enum('marking_method', ['auto', 'manual'])->default('auto');

            $table->timestamps();

            $table->index(['attempt_id', 'question_id']);
            $table->index(['question_id', 'answer_id']);
            $table->unique(['attempt_id','question_id','answer_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('attempt_answers');
    }
};
