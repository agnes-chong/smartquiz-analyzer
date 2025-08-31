<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_id')
                ->constrained()
                ->cascadeOnDelete();

            // Prompt (kept in sync with data->prompt)
            $table->text('text');

            // Normalized types for the API / rubric
            $table->enum('type', ['mcq','tf','short']);

            // Strategy fields
            $table->enum('scoring', ['exact','partial','negative','manual'])->default('exact');
            $table->decimal('marks', 6, 2)->default(1);
            $table->decimal('penalty', 6, 2)->nullable();

            // Rich payload
            $table->json('data')->nullable();    // { prompt, options:[...] }
            $table->json('answer')->nullable();  // canonical answers (array)

            // Legacy mirror of answer for readability/export
            $table->string('correct_answer')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
