<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add columns only if they don't already exist (safe to run on existing DB)
        if (!Schema::hasColumn('quizzes', 'is_active')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('title');
            });
        }

        if (!Schema::hasColumn('quizzes', 'starts_at')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dateTime('starts_at')->nullable()->after('is_active');
            });
        }

        if (!Schema::hasColumn('quizzes', 'ends_at')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dateTime('ends_at')->nullable()->after('starts_at');
            });
        }

        if (!Schema::hasColumn('quizzes', 'duration_minutes')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->integer('duration_minutes')->nullable()->after('ends_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('quizzes', 'duration_minutes')) $table->dropColumn('duration_minutes');
            if (Schema::hasColumn('quizzes', 'ends_at')) $table->dropColumn('ends_at');
            if (Schema::hasColumn('quizzes', 'starts_at')) $table->dropColumn('starts_at');
            if (Schema::hasColumn('quizzes', 'is_active')) $table->dropColumn('is_active');
        });
    }
};
