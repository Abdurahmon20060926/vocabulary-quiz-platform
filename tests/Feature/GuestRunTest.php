<?php

use App\Models\Quiz;
use App\Models\QuizRun;
use App\Models\User;
use Illuminate\Support\Str;

function guestRunQuiz(): Quiz
{
    $creator = User::create([
        'google_id' => 'guest_run_'.Str::random(10),
        'name' => 'Guest Run Creator',
        'email' => Str::random(10).'@guest-run.example',
    ]);

    $quiz = Quiz::create([
        'user_id' => $creator->id,
        'title' => 'Guest Run Quiz',
        'mode' => 'casual',
        'seconds_per_question' => 5,
        'status' => 'published',
        'is_listed' => true,
    ]);

    for ($index = 0; $index < 5; $index++) {
        $quiz->questions()->create([
            'position' => $index,
            'prompt' => "Question {$index}",
            'options' => ['Correct', 'Wrong one', 'Wrong two', 'Wrong three'],
            'correct_option_index' => 0,
        ]);
    }

    return $quiz;
}

test('guest runs are accessible only with the matching guest token', function () {
    $quiz = guestRunQuiz();
    $startResponse = $this->postJson("/q/{$quiz->ulid}/runs")
        ->assertOk();
    $run = QuizRun::findOrFail($startResponse->json('run_id'));
    $guestToken = $startResponse->headers->get('X-Guest-Token');
    $answers = collect($startResponse->json('questions'))->values()->map(fn ($question, $index) => [
        'question_id' => $question['id'],
        'chosen_option_index' => $index === 0 ? 1 : null,
        'time_ms' => $index === 0 ? 500 : 0,
    ])->all();

    expect($guestToken)->toBeString();
    expect($guestToken)->not->toBe('');
    expect($run->user_id)->toBeNull();
    expect($run->guest_token)->toBe($guestToken);

    $this->withHeader('X-Guest-Token', 'another-guest-token')
        ->postJson("/runs/{$run->id}/submit", ['answers' => $answers])
        ->assertForbidden();

    $this->withHeader('X-Guest-Token', $guestToken)
        ->postJson("/runs/{$run->id}/submit", ['answers' => $answers])
        ->assertOk()
        ->assertJson(['verdict' => 'failed', 'failure_reason' => 'wrong_answer']);

    expect($run->fresh()->status)->toBe('failed');
});
