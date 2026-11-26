<?php

use App\Models\Quiz;
use App\Models\QuizUserStat;
use App\Models\User;
use Illuminate\Support\Str;

function playsUser(string $name): User
{
    return User::create([
        'google_id' => 'plays_'.Str::random(10),
        'name' => $name,
        'email' => Str::random(10).'@plays.example',
    ]);
}

function playsQuiz(User $creator, string $title): Quiz
{
    return Quiz::create([
        'user_id' => $creator->id,
        'title' => $title,
        'mode' => 'casual',
        'seconds_per_question' => 5,
        'status' => 'published',
        'is_listed' => true,
    ]);
}

test('My Plays lists only the authenticated user\'s quiz history in most-recent order', function () {
    $player = playsUser('Player');
    $otherPlayer = playsUser('Other Player');
    $creator = playsUser('Creator');
    $olderQuiz = playsQuiz($creator, 'Older quiz');
    $newerQuiz = playsQuiz($creator, 'Newer quiz');
    $otherQuiz = playsQuiz($creator, 'Other player quiz');

    QuizUserStat::create([
        'user_id' => $player->id,
        'quiz_id' => $olderQuiz->id,
        'attempts' => 2,
        'best_reached' => 3,
        'passed' => false,
        'last_played_at' => '2025-01-01 00:00:00',
    ]);
    QuizUserStat::create([
        'user_id' => $player->id,
        'quiz_id' => $newerQuiz->id,
        'attempts' => 1,
        'best_reached' => 5,
        'passed' => true,
        'last_played_at' => '2025-02-01 00:00:00',
    ]);
    QuizUserStat::create([
        'user_id' => $otherPlayer->id,
        'quiz_id' => $otherQuiz->id,
        'attempts' => 9,
        'best_reached' => 4,
        'passed' => false,
        'last_played_at' => '2025-03-01 00:00:00',
    ]);

    $this->actingAs($player)
        ->get('/plays')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MyPlays')
            ->has('stats.data', 2)
            ->where('stats.data.0.quiz.ulid', $newerQuiz->ulid)
            ->where('stats.data.1.quiz.ulid', $olderQuiz->ulid));
});

test('guest is redirected away from My Plays', function () {
    $this->get('/plays')->assertRedirect();
});
