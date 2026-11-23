<?php

use App\Models\Quiz;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Str;

function makeUser(array $attrs = []): User
{
    return User::create(array_merge([
        'google_id' => 'g_' . Str::random(8),
        'name' => 'Test User',
        'email' => Str::random(8) . '@test.example',
    ], $attrs));
}

function makeQuiz(User $user, array $attrs = []): Quiz
{
    return Quiz::create(array_merge([
        'ulid' => strtolower(Str::ulid()),
        'user_id' => $user->id,
        'title' => 'Test Quiz',
        'mode' => 'casual',
        'seconds_per_question' => 10,
        'status' => 'draft',
        'is_listed' => true,
    ], $attrs));
}

function addQuestions(Quiz $quiz, int $count = 5): void
{
    for ($i = 0; $i < $count; $i++) {
        $quiz->questions()->create([
            'position' => $i,
            'prompt' => "Question $i",
            'options' => ["Option A$i", "Option B$i", "Option C$i", "Option D$i"],
            'correct_option_index' => 0,
        ]);
    }
}

// === Lifecycle: Immutability ===

test('published quiz cannot be edited', function () {
    $user = makeUser();
    $quiz = makeQuiz($user, ['status' => 'published']);
    addQuestions($quiz, 5);

    $this->actingAs($user)
        ->putJson("/studio/quizzes/{$quiz->ulid}", [
            'title' => 'New Title',
            'description' => null,
            'mode' => 'strict',
            'seconds_per_question' => 5,
        ])
        ->assertStatus(422);
});

test('only drafts can be deleted', function () {
    $user = makeUser();

    $draft = makeQuiz($user, ['status' => 'draft']);
    $published = makeQuiz($user, ['status' => 'published']);

    $this->actingAs($user)
        ->deleteJson("/studio/quizzes/{$draft->ulid}")
        ->assertStatus(200);

    $this->assertDatabaseMissing('quizzes', ['id' => $draft->id]);

    $this->actingAs($user)
        ->deleteJson("/studio/quizzes/{$published->ulid}")
        ->assertStatus(422);

    $this->assertDatabaseHas('quizzes', ['id' => $published->id]);
});

// === Lifecycle: Publish Validation ===

test('publish requires 5-15 questions', function () {
    $user = makeUser();
    $quiz = makeQuiz($user, ['status' => 'draft']);
    addQuestions($quiz, 3); // too few

    $this->actingAs($user)
        ->postJson("/studio/quizzes/{$quiz->ulid}/publish", ['is_listed' => true])
        ->assertStatus(422)
        ->assertJsonPath('errors.questions.0', fn($v) => str_contains($v, 'between 5 and 15'));
});

test('publish succeeds with 5 valid questions', function () {
    $user = makeUser();
    $quiz = makeQuiz($user, ['status' => 'draft']);
    addQuestions($quiz, 5);

    $this->actingAs($user)
        ->postJson("/studio/quizzes/{$quiz->ulid}/publish", ['is_listed' => true])
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    expect(Quiz::find($quiz->id)->status)->toBe('published');
});

// === Lifecycle: Unpublish Effects ===

test('unpublishing abandons in-progress runs by others', function () {
    $creator = makeUser();
    $player = makeUser();
    $quiz = makeQuiz($creator, ['status' => 'published']);
    addQuestions($quiz, 5);

    // Create an in-progress run by player
    $run = \App\Models\QuizRun::create([
        'quiz_id' => $quiz->id,
        'user_id' => $player->id,
        'mode' => 'casual',
        'question_order' => $quiz->questions()->pluck('id')->toArray(),
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    $this->actingAs($creator)
        ->postJson("/studio/quizzes/{$quiz->ulid}/unpublish")
        ->assertStatus(200);

    expect(\App\Models\QuizRun::find($run->id)->status)->toBe('abandoned');
});

// === Vote Toggle & Own-quiz Rule ===

test('authenticated user can toggle vote on published listed quiz', function () {
    $creator = makeUser();
    $voter = makeUser();
    $quiz = makeQuiz($creator, ['status' => 'published', 'is_listed' => true]);

    // Vote up
    $this->actingAs($voter)
        ->postJson("/q/{$quiz->ulid}/vote")
        ->assertStatus(200)
        ->assertJson(['voted' => true]);

    expect(Quiz::find($quiz->id)->upvotes_count)->toBe(1);

    // Toggle off
    $this->actingAs($voter)
        ->postJson("/q/{$quiz->ulid}/vote")
        ->assertStatus(200)
        ->assertJson(['voted' => false]);

    expect(Quiz::find($quiz->id)->upvotes_count)->toBe(0);
});

test('user cannot vote on own quiz', function () {
    $user = makeUser();
    $quiz = makeQuiz($user, ['status' => 'published', 'is_listed' => true]);

    $this->actingAs($user)
        ->postJson("/q/{$quiz->ulid}/vote")
        ->assertStatus(422);
});

test('guest cannot vote', function () {
    $creator = makeUser();
    $quiz = makeQuiz($creator, ['status' => 'published', 'is_listed' => true]);

    $this->postJson("/q/{$quiz->ulid}/vote")
        ->assertStatus(401);
});

// === Stats: Creator Runs Excluded ===

test('creator runs do not count toward quiz stats', function () {
    $creator = makeUser();
    $quiz = makeQuiz($creator, ['status' => 'published', 'is_listed' => true]);
    addQuestions($quiz, 5);

    $questionIds = $quiz->questions()->pluck('id')->toArray();
    $salt = bin2hex(random_bytes(16));

    $run = \App\Models\QuizRun::create([
        'quiz_id' => $quiz->id,
        'user_id' => $creator->id,
        'mode' => 'casual',
        'question_order' => $questionIds,
        'salt' => $salt,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    $answers = [];
    foreach ($quiz->questions as $q) {
        $answers[] = [
            'question_id' => $q->id,
            'chosen_option_index' => $q->correct_option_index,
            'time_ms' => 1000,
        ];
    }

    $this->actingAs($creator)
        ->postJson("/runs/{$run->id}/submit", ['answers' => $answers])
        ->assertStatus(200)
        ->assertJson(['verdict' => 'passed']);

    $quiz->refresh();
    expect($quiz->runs_count)->toBe(0); // creator excluded
    expect($quiz->passes_count)->toBe(0);
});

// === Casual: Server Rejects Tampered Answers ===

test('casual mode server rejects tampered answers', function () {
    $user = makeUser();
    $quiz = makeQuiz($user, ['status' => 'published', 'is_listed' => true, 'mode' => 'casual']);
    addQuestions($quiz, 5);

    $questionIds = $quiz->questions()->pluck('id')->toArray();
    $salt = bin2hex(random_bytes(16));

    $run = \App\Models\QuizRun::create([
        'quiz_id' => $quiz->id,
        'user_id' => $user->id,
        'mode' => 'casual',
        'question_order' => $questionIds,
        'salt' => $salt,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    // Submit all wrong answers (index 1 when correct is 0)
    $answers = [];
    foreach ($quiz->questions as $q) {
        $answers[] = [
            'question_id' => $q->id,
            'chosen_option_index' => 1, // wrong - correct is 0
            'time_ms' => 1000,
        ];
    }

    $this->actingAs($user)
        ->postJson("/runs/{$run->id}/submit", ['answers' => $answers])
        ->assertStatus(200)
        ->assertJson(['verdict' => 'failed']);
});

// === Suggestion Scoring & Per-creator Cap ===

test('suggestions cap at 3 per creator', function () {
    $creator = makeUser();
    $viewer = makeUser();

    // Create 5 quizzes by same creator
    for ($i = 0; $i < 5; $i++) {
        $q = makeQuiz($creator, ['status' => 'published', 'is_listed' => true]);
        addQuestions($q, 5);
    }

    $baseQuiz = makeQuiz($creator, ['status' => 'published', 'is_listed' => true]);
    addQuestions($baseQuiz, 5);
    $baseQuiz->update(['status' => 'published']);

    $service = new \App\Services\SuggestionService();
    $suggestions = $service->getSuggestions($baseQuiz, $viewer->id);

    $creatorCount = $suggestions->where('user_id', $creator->id)->count();
    expect($creatorCount)->toBeLessThanOrEqual(3);
});
