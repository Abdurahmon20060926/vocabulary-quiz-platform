<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_user_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedSmallInteger('best_reached')->default(0);
            $table->boolean('passed')->default(false);
            $table->timestamp('first_passed_at')->nullable();
            $table->timestamp('last_played_at')->nullable();

            $table->unique(['user_id', 'quiz_id']);
            $table->index(['user_id', 'last_played_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_user_stats');
    }
};
