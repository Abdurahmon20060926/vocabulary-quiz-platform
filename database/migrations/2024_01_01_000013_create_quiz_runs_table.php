<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_runs', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_token', 64)->nullable()->index();
            $table->string('mode', 10); // strict | casual
            $table->json('question_order'); // array of question IDs
            $table->json('option_orders')->nullable(); // strict only
            $table->string('salt', 64)->nullable(); // casual only
            $table->unsignedSmallInteger('current_index')->default(0); // strict only
            $table->timestamp('current_issued_at', 6)->nullable(); // strict only, ms precision
            $table->string('status', 20)->default('in_progress'); // in_progress|passed|failed|abandoned
            $table->string('failure_reason', 20)->nullable(); // wrong_answer|timeout|focus_lost
            $table->unsignedSmallInteger('reached')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->index(['quiz_id', 'user_id', 'status']);
            $table->index(['quiz_id', 'guest_token', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_runs');
    }
};
