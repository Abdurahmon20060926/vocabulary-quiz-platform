<?php

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Str;

function exploreCreator(): User
{
    return User::create([
        'google_id' => 'explore_'.Str::random(10),
        'name' => 'Explore Creator',
        'email' => Str::random(10).'@explore.example',
    ]);
}

function exploreQuiz(User $creator, array $attributes = []): Quiz
{
    return Quiz::create(array_merge([
        'user_id' => $creator->id,
        'title' => 'Explore Quiz',
        'mode' => 'strict',
        'seconds_per_question' => 10,
        'status' => 'published',
        'is_listed' => true,
        'published_at' => '2025-01-01 00:00:00',
    ], $attributes));
}

test('explore search matches titles and descriptions while applying mode and visibility filters', function () {
    $creator = exploreCreator();
    $titleMatch = exploreQuiz($creator, [
        'title' => 'Needle in title',
        'upvotes_count' => 2,
    ]);
    $descriptionMatch = exploreQuiz($creator, [
        'title' => 'Another title',
        'description' => 'A needle in this description',
        'upvotes_count' => 1,
    ]);
    exploreQuiz($creator, ['title' => 'Needle in another mode', 'mode' => 'casual']);
    exploreQuiz($creator, ['title' => 'Hidden needle', 'is_listed' => false]);
    exploreQuiz($creator, ['title' => 'Draft needle', 'status' => 'draft']);

    $this->get('/?search=needle&mode=strict')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Explore')
            ->where('filters.search', 'needle')
            ->where('filters.mode', 'strict')
            ->has('quizzes.data', 2)
            ->where('quizzes.data.0.ulid', $titleMatch->ulid)
            ->where('quizzes.data.1.ulid', $descriptionMatch->ulid));
});

test('explore sorts published quizzes by popularity, plays, and publication date', function () {
    $creator = exploreCreator();
    $popular = exploreQuiz($creator, [
        'title' => 'Most popular',
        'upvotes_count' => 12,
        'runs_count' => 2,
        'published_at' => '2025-01-01 00:00:00',
    ]);
    $mostPlayed = exploreQuiz($creator, [
        'title' => 'Most played',
        'upvotes_count' => 1,
        'runs_count' => 15,
        'published_at' => '2025-02-01 00:00:00',
    ]);
    $newest = exploreQuiz($creator, [
        'title' => 'Newest',
        'upvotes_count' => 4,
        'runs_count' => 5,
        'published_at' => '2025-03-01 00:00:00',
    ]);

    $this->get('/?sort=popular')
        ->assertInertia(fn ($page) => $page->where('quizzes.data.0.ulid', $popular->ulid));
    $this->get('/?sort=played')
        ->assertInertia(fn ($page) => $page->where('quizzes.data.0.ulid', $mostPlayed->ulid));
    $this->get('/?sort=newest')
        ->assertInertia(fn ($page) => $page->where('quizzes.data.0.ulid', $newest->ulid));
});
