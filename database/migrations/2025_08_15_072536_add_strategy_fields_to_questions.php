<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $t) {
            if (!Schema::hasColumn('questions','scoring')) {
                $t->enum('scoring', ['exact','partial','negative'])
                  ->default('exact')->after('type');
            }
            if (!Schema::hasColumn('questions','marks')) {
                $t->decimal('marks', 6, 2)->default(1)->after('scoring');
            }
            if (!Schema::hasColumn('questions','penalty')) {
                $t->decimal('penalty', 6, 2)->nullable()->after('marks');
            }
            // If your MySQL/MariaDB doesn’t support JSON, change both json() to text()
            if (!Schema::hasColumn('questions','data')) {
                $t->json('data')->nullable()->after('penalty');
            }
            if (!Schema::hasColumn('questions','answer')) {
                $t->json('answer')->nullable()->after('data');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $t) {
            foreach (['answer','data','penalty','marks','scoring'] as $col) {
                if (Schema::hasColumn('questions', $col)) $t->dropColumn($col);
            }
        });
    }
};
