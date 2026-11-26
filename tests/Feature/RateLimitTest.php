<?php

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Str;

function rateLimitUser(string $name): User
{
    return User::create([
        'google_id' => 'limit_'.Str::random(10),
        'name' => $name,
        'email' => Str::random(10).'@limit.example',
    ]);
}

function rateLimitQuiz(User $creator, string $status = 'published'): Quiz
{
    $quiz = Quiz::create([
        'user_id' => $creator->id,
        'title' => 'Rate Limited Quiz',
        'mode' => 'strict',
        'seconds_per_question' => 10,
        'status' => $status,
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

test('authenticated players are limited to 30 run starts per minute', function () {
    $creator = rateLimitUser('Creator');
    $player = rateLimitUser('Player');
    $quiz = rateLimitQuiz($creator);

    for ($attempt = 0; $attempt < 30; $attempt++) {
        $this->actingAs($player)
            ->postJson("/q/{$quiz->ulid}/runs")
            ->assertOk();
    }

    $this->actingAs($player)
        ->postJson("/q/{$quiz->ulid}/runs")
        ->assertTooManyRequests();

    expect($quiz->runs()->count())->toBe(30);
});

test('players are limited to 30 votes per minute', function () {
    $creator = rateLimitUser('Creator');
    $voter = rateLimitUser('Voter');
    $quiz = rateLimitQuiz($creator);

    for ($attempt = 0; $attempt < 30; $attempt++) {
        $this->actingAs($voter)
            ->postJson("/q/{$quiz->ulid}/vote")
            ->assertOk();
    }

    $this->actingAs($voter)
        ->postJson("/q/{$quiz->ulid}/vote")
        ->assertTooManyRequests();

    expect($quiz->fresh()->upvotes_count)->toBe(0);
    expect($voter->votes()->count())->toBe(0);
});
