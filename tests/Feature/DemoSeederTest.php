<?php

use App\Models\Quiz;
use Database\Seeders\DemoQuizSeeder;

test('demo quiz seeding is idempotent and visible to guests', function () {
    (new DemoQuizSeeder)->run();
    (new DemoQuizSeeder)->run();

    $demoQuizzes = Quiz::where('status', 'published')
        ->where('is_listed', true)
        ->whereIn('ulid', [
            '01ARZ3NDEKTSV4RRFFQ69G5FAV',
            '01ARZ3NDEKTSV4RRFFQ69G5FAW',
            '01ARZ3NDEKTSV4RRFFQ69G5FAX',
            '01ARZ3NDEKTSV4RRFFQ69G5FAY',
            '01ARZ3NDEKTSV4RRFFQ69G5FAZ',
        ])
        ->with('questions')
        ->get();

    expect($demoQuizzes)->toHaveCount(5);

    foreach ($demoQuizzes as $quiz) {
        expect($quiz->questions->count())
            ->toBeGreaterThanOrEqual(5)
            ->toBeLessThanOrEqual(15);

        foreach ($quiz->questions as $question) {
            expect($question->options)->toHaveCount(4);

            $normalized = array_map(
                fn (mixed $option): string => mb_strtolower(trim((string) $option)),
                $question->options,
            );

            expect(count(array_unique($normalized)))->toBe(4);
        }
    }

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Explore')
            ->has('quizzes.data', 5));
});
