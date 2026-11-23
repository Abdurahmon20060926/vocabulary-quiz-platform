<?php

use App\Models\Quiz;
use App\Models\QuizRun;
use App\Models\User;
use Illuminate\Support\Str;

function strictUser(array $attrs = []): User
{
    return User::create(array_merge([
        'google_id' => 'sg_' . Str::random(8),
        'name' => 'Strict Tester',
        'email' => Str::random(8) . '@strict.example',
    ], $attrs));
}

function makeStrictQuiz(User $user): Quiz
{
    $quiz = Quiz::create([
        'ulid' => strtolower(Str::ulid()),
        'user_id' => $user->id,
        'title' => 'Strict Quiz',
        'mode' => 'strict',
        'seconds_per_question' => 10,
        'status' => 'published',
        'is_listed' => true,
    ]);

    for ($i = 0; $i < 5; $i++) {
        $quiz->questions()->create([
            'position' => $i,
            'prompt' => "Question $i",
            'options' => ["Correct$i", "Wrong1-$i", "Wrong2-$i", "Wrong3-$i"],
            'correct_option_index' => 0,
        ]);
    }

    return $quiz;
}

function startStrictRun(Quiz $quiz, User $user): array
{
    $questions = $quiz->questions()->get();
    $questionIds = $questions->pluck('id')->toArray();
    shuffle($questionIds);

    $optionOrders = [];
    foreach ($questionIds as $qid) {
        // Put correct (index 0) always at display position 0 for predictable tests
        $optionOrders[$qid] = [0, 1, 2, 3];
    }

    $run = QuizRun::create([
        'quiz_id' => $quiz->id,
        'user_id' => $user->id,
        'mode' => 'strict',
        'question_order' => $questionIds,
        'option_orders' => $optionOrders,
        'current_index' => 0,
        'current_issued_at' => now(),
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    return ['run' => $run, 'questionIds' => $questionIds];
}

// === Hidden Answers ===

test('strict run start does not expose answer data', function () {
    $user = strictUser();
    $quiz = makeStrictQuiz($user);

    $response = $this->actingAs($user)
        ->postJson("/q/{$quiz->ulid}/runs")
        ->assertStatus(200);

    $data = $response->json();

    expect($data)->toHaveKey('run_id');
    expect($data)->toHaveKey('question');
    expect($data['question'])->not->toHaveKey('correct_option_index');
    expect($data['question'])->not->toHaveKey('answer_hash');
    expect($data)->not->toHaveKey('questions'); // no bulk questions in strict
});

// === Sudden Death ===

test('wrong answer ends strict run immediately', function () {
    $user = strictUser();
    $quiz = makeStrictQuiz($user);
    ['run' => $run] = startStrictRun($quiz, $user);

    // Submit wrong answer (display index 1 = original index 1 = wrong)
    $response = $this->actingAs($user)
        ->postJson("/runs/{$run->id}/answers", ['displayed_index' => 1])
        ->assertStatus(200);

    expect($response->json('verdict'))->toBe('failed');
    expect($response->json('failure_reason'))->toBe('wrong_answer');
    expect(QuizRun::find($run->id)->status)->toBe('failed');
});

test('correct answer advances to next question', function () {
    $user = strictUser();
    $quiz = makeStrictQuiz($user);
    ['run' => $run] = startStrictRun($quiz, $user);

    // Display index 0 = original index 0 = correct
    $response = $this->actingAs($user)
        ->postJson("/runs/{$run->id}/answers", ['displayed_index' => 0])
        ->assertStatus(200);

    expect($response->json('verdict'))->toBe('correct');
    expect($response->json('next_question'))->toBeArray();
    expect($response->json('next_question.index'))->toBe(1);
});

// === Timing Tests ===

test('answer within grace period is accepted', function () {
    $user = strictUser();
    $quiz = makeStrictQuiz($user);
    ['run' => $run] = startStrictRun($quiz, $user);

    // issued_at is now - well within grace
    $response = $this->actingAs($user)
        ->postJson("/runs/{$run->id}/answers", ['displayed_index' => 0])
        ->assertStatus(200);

    expect($response->json('verdict'))->toBe('correct');
});

test('answer past time limit counts as timeout', function () {
    $user = strictUser();
    $quiz = makeStrictQuiz($user); // 10s per question
    ['run' => $run] = startStrictRun($quiz, $user);

    // Backdate current_issued_at by 15 seconds (well past limit + grace)
    $run->update(['current_issued_at' => now()->subSeconds(15)]);

    $response = $this->actingAs($user)
        ->postJson("/runs/{$run->id}/answers", ['displayed_index' => 0])
        ->assertStatus(200);

    expect($response->json('verdict'))->toBe('failed');
    expect($response->json('failure_reason'))->toBe('timeout');
});

test('focus_lost reason ends run immediately', function () {
    $user = strictUser();
    $quiz = makeStrictQuiz($user);
    ['run' => $run] = startStrictRun($quiz, $user);

    $response = $this->actingAs($user)
        ->postJson("/runs/{$run->id}/answers", ['reason' => 'focus_lost'])
        ->assertStatus(200);

    expect($response->json('verdict'))->toBe('failed');
    expect($response->json('failure_reason'))->toBe('focus_lost');
});

// === Authorization ===

test('run endpoint rejects unauthorized user', function () {
    $owner = strictUser();
    $other = strictUser();
    $quiz = makeStrictQuiz($owner);
    ['run' => $run] = startStrictRun($quiz, $owner);

    $this->actingAs($other)
        ->postJson("/runs/{$run->id}/answers", ['displayed_index' => 0])
        ->assertStatus(403);
});

// === Pass All Questions ===

test('answering all questions correctly passes the run', function () {
    $user = strictUser();
    $quiz = makeStrictQuiz($user);

    $questions = $quiz->questions()->get();
    $questionIds = $questions->pluck('id')->toArray();

    // Build with identity perm so display=0 is always correct
    $optionOrders = [];
    foreach ($questionIds as $qid) {
        $optionOrders[$qid] = [0, 1, 2, 3];
    }

    $run = QuizRun::create([
        'quiz_id' => $quiz->id,
        'user_id' => $user->id,
        'mode' => 'strict',
        'question_order' => $questionIds,
        'option_orders' => $optionOrders,
        'current_index' => 0,
        'current_issued_at' => now(),
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    // Answer all questions correctly
    $totalQuestions = count($questionIds);
    for ($i = 0; $i < $totalQuestions; $i++) {
        $response = $this->actingAs($user)
            ->postJson("/runs/{$run->id}/answers", ['displayed_index' => 0]);

        if ($i < $totalQuestions - 1) {
            expect($response->json('verdict'))->toBe('correct');
        } else {
            expect($response->json('verdict'))->toBe('passed');
        }
    }

    $run->refresh();
    expect($run->status)->toBe('passed');
    expect($run->reached)->toBe($totalQuestions);
});
