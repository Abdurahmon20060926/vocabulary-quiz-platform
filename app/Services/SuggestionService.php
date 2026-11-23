<?php

namespace App\Services;

use App\Models\Quiz;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SuggestionService
{
    public function getSuggestions(Quiz $currentQuiz, ?int $userId): Collection
    {
        $wAuthor = (float) config('quiz.suggestion_w_author', 3);
        $wStats = (float) config('quiz.suggestion_w_stats', 2);
        $wVotes = (float) config('quiz.suggestion_w_votes', 1);

        // Pass rate of current quiz
        $currentPassRate = $currentQuiz->runs_count >= 10
            ? ($currentQuiz->runs_count > 0 ? $currentQuiz->passes_count / $currentQuiz->runs_count : 0)
            : 0.5;

        $query = Quiz::query()
            ->where('status', 'published')
            ->where('is_listed', true)
            ->where('id', '!=', $currentQuiz->id)
            ->with('user:id,name,avatar_url')
            ->withCount('questions');

        // Exclude quizzes the user has already passed
        if ($userId) {
            $query->whereNotIn('id', function ($sub) use ($userId) {
                $sub->select('quiz_id')
                    ->from('quiz_user_stats')
                    ->where('user_id', $userId)
                    ->where('passed', true);
            });
        }

        $candidates = $query->get();

        // Score each candidate in PHP (for SQLite compatibility)
        $scored = $candidates->map(function (Quiz $quiz) use ($currentQuiz, $currentPassRate, $wAuthor, $wStats, $wVotes) {
            $sameCreator = $quiz->user_id === $currentQuiz->user_id ? 1 : 0;

            $candidatePassRate = $quiz->runs_count >= 10
                ? ($quiz->runs_count > 0 ? $quiz->passes_count / $quiz->runs_count : 0)
                : 0.5;

            $statsTerm = 1 - abs($currentPassRate - $candidatePassRate);
            $votesTerm = log10(1 + $quiz->upvotes_count);

            $score = $wAuthor * $sameCreator
                + $wStats * $statsTerm
                + $wVotes * $votesTerm;

            return [
                'quiz' => $quiz,
                'score' => $score,
            ];
        });

        // Sort by score descending, cap 3 per creator
        $sorted = $scored->sortByDesc('score');
        $result = collect();
        $perCreator = [];

        foreach ($sorted as $item) {
            $creatorId = $item['quiz']->user_id;
            $perCreator[$creatorId] = ($perCreator[$creatorId] ?? 0);

            if ($perCreator[$creatorId] >= 3) {
                continue;
            }

            $result->push($item['quiz']);
            $perCreator[$creatorId]++;

            if ($result->count() >= 12) {
                break;
            }
        }

        return $result;
    }
}
