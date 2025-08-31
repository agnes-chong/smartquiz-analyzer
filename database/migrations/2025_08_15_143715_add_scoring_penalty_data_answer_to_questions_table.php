<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add EACH column only if it's missing, in separate Schema::table calls.
        if (!Schema::hasColumn('questions', 'scoring')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->enum('scoring', ['exact','partial','negative'])
                      ->default('exact')
                      ->after('type');
            });
        }

        if (!Schema::hasColumn('questions', 'marks')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->decimal('marks', 6, 2)->default(1)->after('scoring');
            });
        }

        if (!Schema::hasColumn('questions', 'penalty')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->decimal('penalty', 6, 2)->nullable()->after('marks');
            });
        }

        if (!Schema::hasColumn('questions', 'data')) {
            Schema::table('questions', function (Blueprint $table) {
                // If your DB doesn't support JSON, change to ->text('data')
                $table->json('data')->nullable()->after('penalty');
            });
        }

        if (!Schema::hasColumn('questions', 'answer')) {
            Schema::table('questions', function (Blueprint $table) {
                // If your DB doesn't support JSON, change to ->text('answer')
                $table->json('answer')->nullable()->after('data');
            });
        }
    }

    public function down(): void
    {
        // Drop only if present (safe rollback)
        if (Schema::hasColumn('questions', 'answer')) {
            Schema::table('questions', fn (Blueprint $table) => $table->dropColumn('answer'));
        }
        if (Schema::hasColumn('questions', 'data')) {
            Schema::table('questions', fn (Blueprint $table) => $table->dropColumn('data'));
        }
        if (Schema::hasColumn('questions', 'penalty')) {
            Schema::table('questions', fn (Blueprint $table) => $table->dropColumn('penalty'));
        }
        if (Schema::hasColumn('questions', 'marks')) {
            Schema::table('questions', fn (Blueprint $table) => $table->dropColumn('marks'));
        }
        if (Schema::hasColumn('questions', 'scoring')) {
            Schema::table('questions', fn (Blueprint $table) => $table->dropColumn('scoring'));
        }
    }
};
