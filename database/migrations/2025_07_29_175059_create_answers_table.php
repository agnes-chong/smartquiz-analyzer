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
    Schema::create('answers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('question_id')->constrained();
        $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
        $table->text('response');
        $table->boolean('is_correct')->default(false);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('answers');
    }
};
