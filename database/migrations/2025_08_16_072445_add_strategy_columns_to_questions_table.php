<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'scoring')) {
                $table->enum('scoring', ['exact','partial','negative','manual'])->default('exact');
            }
            if (!Schema::hasColumn('questions', 'marks')) {
                $table->decimal('marks', 6, 2)->default(1);
            }
            if (!Schema::hasColumn('questions', 'penalty')) {
                $table->decimal('penalty', 6, 2)->nullable();
            }
            if (!Schema::hasColumn('questions', 'data')) {
                $table->json('data')->nullable();
            }
            if (!Schema::hasColumn('questions', 'answer')) {
                $table->json('answer')->nullable();
            }
            if (!Schema::hasColumn('questions', 'correct_answer')) {
                $table->string('correct_answer')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Usually leave as-is or drop conditionally if present.
        Schema::table('questions', function (Blueprint $table) {
            // no-op for safety in assignments
        });
    }
};
