<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('run_answers', function (Blueprint $table) {
            $table->id();
            $table->string('quiz_run_id', 26);
            $table->foreign('quiz_run_id')->references('id')->on('quiz_runs')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('chosen_option_index')->nullable(); // original index
            $table->boolean('is_correct');
            $table->string('failure_reason', 20)->nullable(); // wrong_answer|timeout|focus_lost
            $table->unsignedInteger('time_ms')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index('quiz_run_id');
            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('run_answers');
    }
};
