<?php

use App\Models\Quiz;
use App\Models\QuizRun;
use App\Models\QuizUserStat;
use App\Models\RunAnswer;
use App\Models\User;
use App\Services\SuggestionService;
use Illuminate\Support\Str;

function makeUser(array $attrs = []): User
{
    return User::create(array_merge([
        'google_id' => 'g_'.Str::random(8),
        'name' => 'Test User',
        'email' => Str::random(8).'@test.example',
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

test('only published or unpublished quizzes can be duplicated and the copy title is capped', function () {
    $user = makeUser();
    $draft = makeQuiz($user);
    $published = makeQuiz($user, ['status' => 'published', 'title' => str_repeat('Q', 100)]);
    $untitled = makeQuiz($user, ['status' => 'unpublished', 'title' => null]);
    addQuestions($published, 5);

    $this->actingAs($user)
        ->postJson("/studio/quizzes/{$draft->ulid}/duplicate")
        ->assertUnprocessable();

    $response = $this->actingAs($user)
        ->postJson("/studio/quizzes/{$published->ulid}/duplicate")
        ->assertOk();

    $copy = Quiz::where('ulid', $response->json('ulid'))->firstOrFail();
    expect(mb_strlen($copy->title))->toBeLessThanOrEqual(80);
    expect(str_ends_with($copy->title, ' (copy)'))->toBeTrue();
    expect($copy->questions()->count())->toBe(5);

    $untitledCopyResponse = $this->actingAs($user)
        ->postJson("/studio/quizzes/{$untitled->ulid}/duplicate")
        ->assertOk();
    expect(Quiz::where('ulid', $untitledCopyResponse->json('ulid'))->value('title'))
        ->toBe('Untitled (copy)');
});

test('quiz runs require exactly one user or guest owner', function () {
    $user = makeUser();
    $quiz = makeQuiz($user);
    $attributes = [
        'quiz_id' => $quiz->id,
        'mode' => 'casual',
        'question_order' => [],
        'status' => 'in_progress',
    ];

    expect(fn () => QuizRun::create($attributes))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => QuizRun::create($attributes + [
        'user_id' => $user->id,
        'guest_token' => 'guest-token',
    ]))->toThrow(InvalidArgumentException::class);
    expect(QuizRun::create($attributes + ['guest_token' => 'guest-token']))
        ->toBeInstanceOf(QuizRun::class);
});

// === Lifecycle: Publish Validation ===

test('publish requires 5-15 questions', function () {
    $user = makeUser();
    $quiz = makeQuiz($user, ['status' => 'draft']);
    addQuestions($quiz, 3); // too few

    $this->actingAs($user)
        ->postJson("/studio/quizzes/{$quiz->ulid}/publish", ['is_listed' => true])
        ->assertStatus(422)
        ->assertJsonPath('errors.questions.0', fn ($v) => str_contains($v, 'between 5 and 15'));
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
    $run = QuizRun::create([
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

    expect(QuizRun::find($run->id)->status)->toBe('in_progress');

    $this->actingAs($player)
        ->postJson("/runs/{$run->id}/submit", ['answers' => []])
        ->assertOk()
        ->assertJson(['verdict' => 'abandoned', 'reason' => 'quiz_unavailable']);

    expect(QuizRun::find($run->id)->status)->toBe('abandoned');
});

test('creator can view and start an unpublished quiz while other players cannot', function () {
    $creator = makeUser();
    $player = makeUser();
    $quiz = makeQuiz($creator, ['status' => 'unpublished', 'mode' => 'strict']);
    addQuestions($quiz, 5);

    $this->actingAs($creator)
        ->get("/q/{$quiz->ulid}")
        ->assertInertia(fn ($page) => $page
            ->component('Quiz')
            ->where('quiz.ulid', $quiz->ulid));

    $this->actingAs($creator)
        ->postJson("/q/{$quiz->ulid}/runs")
        ->assertOk()
        ->assertJsonStructure(['run_id', 'mode', 'question']);

    $this->actingAs($player)
        ->get("/q/{$quiz->ulid}")
        ->assertInertia(fn ($page) => $page->component('QuizUnavailable'));

    $this->actingAs($player)
        ->postJson("/q/{$quiz->ulid}/runs")
        ->assertNotFound();

    $this->postJson("/q/{$quiz->ulid}/runs")
        ->assertNotFound();
});

test('draft saving allows missing quiz fields and incomplete question data', function () {
    $user = makeUser();

    $response = $this->actingAs($user)
        ->postJson('/studio/quizzes', [])
        ->assertCreated()
        ->assertJsonStructure(['ulid', 'redirect']);

    $quiz = Quiz::where('ulid', $response->json('ulid'))->firstOrFail();
    expect($quiz->title)->toBeNull()
        ->and($quiz->mode)->toBeNull()
        ->and($quiz->seconds_per_question)->toBeNull();

    $questionResponse = $this->actingAs($user)
        ->postJson("/studio/quizzes/{$quiz->ulid}/questions", [
            'prompt' => '',
            'options' => ['partial option'],
        ])
        ->assertCreated()
        ->assertJsonPath('question.prompt', '')
        ->assertJsonPath('question.options', ['partial option', '', '', ''])
        ->assertJsonPath('question.correct_option_index', null);

    expect($questionResponse->json('question.options'))->toHaveCount(4);
});

test('publishing rejects drafts without a title and complete questions', function () {
    $user = makeUser();
    $quiz = makeQuiz($user, ['title' => null, 'mode' => null, 'seconds_per_question' => null]);
    addQuestions($quiz, 5);
    $quiz->questions()->first()->update([
        'options' => ['Same', ' same ', '', 'Other'],
        'correct_option_index' => null,
    ]);

    $this->actingAs($user)
        ->postJson("/studio/quizzes/{$quiz->ulid}/publish", ['is_listed' => true])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);

    $quiz->update(['title' => 'Ready title', 'mode' => 'casual', 'seconds_per_question' => 5]);

    $this->actingAs($user)
        ->postJson("/studio/quizzes/{$quiz->ulid}/publish", ['is_listed' => true])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['questions']);
});

test('creator runs are excluded from per-question and player analytics', function () {
    $creator = makeUser();
    $player = makeUser();
    $quiz = makeQuiz($creator, ['status' => 'published']);
    addQuestions($quiz, 5);
    $question = $quiz->questions()->first();

    foreach ([$creator, $player] as $user) {
        $run = QuizRun::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'mode' => 'casual',
            'question_order' => [$question->id],
            'status' => 'failed',
            'reached' => 0,
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        RunAnswer::create([
            'quiz_run_id' => $run->id,
            'question_id' => $question->id,
            'chosen_option_index' => 1,
            'is_correct' => false,
            'failure_reason' => 'wrong_answer',
            'time_ms' => 500,
            'created_at' => now(),
        ]);

        QuizUserStat::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'attempts' => 1,
            'best_reached' => 0,
            'passed' => false,
            'last_played_at' => now(),
        ]);
    }

    $this->actingAs($creator)
        ->get("/studio/quizzes/{$quiz->ulid}/stats")
        ->assertInertia(fn ($page) => $page
            ->where('questionStats.0.total_answers', 1)
            ->where('uniquePlayers', 1)
            ->has('playerStats', 1)
            ->where('playerStats.0.user_id', $player->id));
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

    $run = QuizRun::create([
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

    $run = QuizRun::create([
        'quiz_id' => $quiz->id,
        'user_id' => $user->id,
        'mode' => 'casual',
        'question_order' => $questionIds,
        'salt' => $salt,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    // The client shuffles questions and stops logging after the first wrong answer.
    $answers = [];
    foreach (array_reverse($questionIds) as $index => $questionId) {
        $answers[] = [
            'question_id' => $questionId,
            'chosen_option_index' => $index === 0 ? 1 : null,
            'time_ms' => $index === 0 ? 1000 : 0,
        ];
    }

    $this->actingAs($user)
        ->postJson("/runs/{$run->id}/submit", ['answers' => $answers])
        ->assertStatus(200)
        ->assertJson(['verdict' => 'failed', 'failure_reason' => 'wrong_answer']);

    expect($run->answers()->count())->toBe(1);
});

test('casual mode rejects an incomplete answer log', function () {
    $user = makeUser();
    $quiz = makeQuiz($user, ['status' => 'published', 'mode' => 'casual']);
    addQuestions($quiz, 5);

    $run = QuizRun::create([
        'quiz_id' => $quiz->id,
        'user_id' => $user->id,
        'mode' => 'casual',
        'question_order' => $quiz->questions()->pluck('id')->toArray(),
        'salt' => bin2hex(random_bytes(16)),
        'status' => 'in_progress',
    ]);

    $this->actingAs($user)
        ->postJson("/runs/{$run->id}/submit", ['answers' => []])
        ->assertStatus(422)
        ->assertJson(['error' => 'The answer log is incomplete.']);

    expect($run->fresh()->status)->toBe('in_progress');
});

test('casual submission requires the exact question set and cannot be counted twice', function () {
    $creator = makeUser();
    $player = makeUser();
    $quiz = makeQuiz($creator, ['status' => 'published', 'mode' => 'casual']);
    addQuestions($quiz, 5);
    $questionIds = $quiz->questions()->pluck('id')->toArray();
    $run = QuizRun::create([
        'quiz_id' => $quiz->id,
        'user_id' => $player->id,
        'mode' => 'casual',
        'question_order' => $questionIds,
        'salt' => bin2hex(random_bytes(16)),
        'status' => 'in_progress',
    ]);

    $validAnswers = [];
    foreach (array_reverse($quiz->questions->all()) as $question) {
        $validAnswers[] = [
            'question_id' => $question->id,
            'chosen_option_index' => $question->correct_option_index,
            'time_ms' => 1000,
        ];
    }

    $this->actingAs($player)
        ->postJson("/runs/{$run->id}/submit", ['answers' => [
            ...array_slice($validAnswers, 0, 4),
            ['question_id' => $validAnswers[0]['question_id'], 'chosen_option_index' => 0, 'time_ms' => 0],
        ]])
        ->assertUnprocessable()
        ->assertJson(['error' => 'The answer log is invalid.']);

    $this->actingAs($player)
        ->postJson("/runs/{$run->id}/submit", ['answers' => $validAnswers])
        ->assertOk()
        ->assertJson(['verdict' => 'passed']);

    $this->actingAs($player)
        ->postJson("/runs/{$run->id}/submit", ['answers' => $validAnswers])
        ->assertUnprocessable()
        ->assertJson(['error' => 'Run is not in progress.']);

    expect($run->answers()->count())->toBe(5);
    expect($quiz->fresh()->runs_count)->toBe(1);
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

    $service = new SuggestionService;
    $suggestions = $service->getSuggestions($baseQuiz, $viewer->id);

    $creatorCount = $suggestions->where('user_id', $creator->id)->count();
    expect($creatorCount)->toBeLessThanOrEqual(3);
});
