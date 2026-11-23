<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 80);
            $table->text('description')->nullable();
            $table->string('mode', 10)->default('casual'); // strict | casual
            $table->unsignedSmallInteger('seconds_per_question')->default(10);
            $table->string('status', 20)->default('draft'); // draft | published | unpublished
            $table->boolean('is_listed')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('upvotes_count')->default(0);
            $table->unsignedInteger('runs_count')->default(0);
            $table->unsignedInteger('passes_count')->default(0);
            $table->unsignedInteger('reached_sum')->default(0);
            $table->timestamps();

            $table->index(['status', 'is_listed']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
