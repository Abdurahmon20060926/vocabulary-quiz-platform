<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizUserStat;
use App\Services\SuggestionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function __construct(private SuggestionService $suggestionService) {}

    public function show(Request $request, Quiz $quiz): Response|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        // Authorization: drafts and unpublished only visible to creator
        if ($quiz->isDraft()) {
            if (!$user || $user->id !== $quiz->user_id) {
                abort(404);
            }
        }

        if ($quiz->isUnpublished()) {
            if (!$user || $user->id !== $quiz->user_id) {
                return Inertia::render('QuizUnavailable', [
                    'quiz' => ['title' => $quiz->title],
                ]);
            }
        }

        $quiz->load('user:id,name,avatar_url');
        $quiz->loadCount('questions');

        $userStats = null;
        $hasVoted = false;

        if ($user) {
            $stat = QuizUserStat::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->first();

            if ($stat) {
                $userStats = [
                    'attempts' => $stat->attempts,
                    'best_reached' => $stat->best_reached,
                    'passed' => $stat->passed,
                    'first_passed_at' => $stat->first_passed_at,
                ];
            }

            $hasVoted = $user->votes()->where('quiz_id', $quiz->id)->exists();
        }

        $suggestions = $this->suggestionService->getSuggestions($quiz, $user?->id);

        $passRate = $quiz->runs_count >= 10 && $quiz->runs_count > 0
            ? round($quiz->passes_count / $quiz->runs_count * 100, 1)
            : null;

        $avgReached = $quiz->runs_count >= 10 && $quiz->runs_count > 0
            ? round($quiz->reached_sum / $quiz->runs_count, 1)
            : null;

        return Inertia::render('Quiz', [
            'quiz' => [
                'id' => $quiz->id,
                'ulid' => $quiz->ulid,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'mode' => $quiz->mode,
                'seconds_per_question' => $quiz->seconds_per_question,
                'status' => $quiz->status,
                'is_listed' => $quiz->is_listed,
                'upvotes_count' => $quiz->upvotes_count,
                'runs_count' => $quiz->runs_count,
                'questions_count' => $quiz->questions_count,
                'pass_rate' => $passRate,
                'avg_reached' => $avgReached,
                'user' => $quiz->user ? [
                    'id' => $quiz->user->id,
                    'name' => $quiz->user->name,
                    'avatar_url' => $quiz->user->avatar_url,
                ] : null,
            ],
            'userStats' => $userStats,
            'hasVoted' => $hasVoted,
            'isCreator' => $user && $user->id === $quiz->user_id,
            'suggestions' => $suggestions->map(fn($s) => [
                'ulid' => $s->ulid,
                'title' => $s->title,
                'mode' => $s->mode,
                'seconds_per_question' => $s->seconds_per_question,
                'upvotes_count' => $s->upvotes_count,
                'runs_count' => $s->runs_count,
                'questions_count' => $s->questions_count,
                'user' => $s->user ? [
                    'name' => $s->user->name,
                    'avatar_url' => $s->user->avatar_url,
                ] : null,
            ])->values(),
        ]);
    }

    public function vote(Request $request, Quiz $quiz): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if (!$quiz->isPublished() || !$quiz->is_listed) {
            return response()->json(['error' => 'Cannot vote on this quiz'], 422);
        }

        if ($quiz->user_id === $user->id) {
            return response()->json(['error' => 'Cannot vote on your own quiz'], 422);
        }

        $existing = $user->votes()->where('quiz_id', $quiz->id)->first();

        if ($existing) {
            \DB::transaction(function () use ($existing, $quiz) {
                $existing->delete();
                \DB::table('quizzes')->where('id', $quiz->id)
                    ->update(['upvotes_count' => \DB::raw('CASE WHEN upvotes_count > 0 THEN upvotes_count - 1 ELSE 0 END')]);
            });
            $voted = false;
        } else {
            \DB::transaction(function () use ($user, $quiz) {
                $user->votes()->create(['quiz_id' => $quiz->id]);
                \DB::table('quizzes')->where('id', $quiz->id)
                    ->update(['upvotes_count' => \DB::raw('upvotes_count + 1')]);
            });
            $voted = true;
        }

        $quiz->refresh();
        return response()->json([
            'voted' => $voted,
            'upvotes_count' => $quiz->upvotes_count,
        ]);
    }
}
