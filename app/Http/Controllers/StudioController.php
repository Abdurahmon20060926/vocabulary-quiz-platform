<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizRun;
use App\Models\QuizUserStat;
use App\Models\RunAnswer;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudioController extends Controller
{
    public function __construct(private QuizService $quizService) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $quizzes = $user->quizzes()
            ->withCount('questions')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($q) => [
                'id' => $q->id,
                'ulid' => $q->ulid,
                'title' => $q->title,
                'mode' => $q->mode,
                'status' => $q->status,
                'is_listed' => $q->is_listed,
                'questions_count' => $q->questions_count,
                'seconds_per_question' => $q->seconds_per_question,
                'runs_count' => $q->runs_count,
                'upvotes_count' => $q->upvotes_count,
                'published_at' => $q->published_at,
                'updated_at' => $q->updated_at,
            ]);

        return Inertia::render('Studio/Index', [
            'quizzes' => $quizzes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Studio/Edit', [
            'quiz' => null,
            'questions' => [],
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:80',
            'description' => 'nullable|string|max:500',
            'mode' => 'nullable|in:strict,casual',
            'seconds_per_question' => 'nullable|integer|min:2|max:10',
        ]);

        $quiz = $this->quizService->createDraft($request->user(), $data);

        if ($request->wantsJson()) {
            return response()->json([
                'ulid' => $quiz->ulid,
                'redirect' => route('studio.quizzes.edit', $quiz->ulid),
            ], 201);
        }

        return redirect()->route('studio.quizzes.edit', $quiz->ulid);
    }

    public function edit(Request $request, Quiz $quiz): Response
    {
        $this->authorizeCreator($request, $quiz);

        return Inertia::render('Studio/Edit', [
            'quiz' => [
                'id' => $quiz->id,
                'ulid' => $quiz->ulid,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'mode' => $quiz->mode,
                'seconds_per_question' => $quiz->seconds_per_question,
                'status' => $quiz->status,
                'is_listed' => $quiz->is_listed,
            ],
            'questions' => $quiz->questions->map(fn ($q) => [
                'id' => $q->id,
                'position' => $q->position,
                'prompt' => $q->prompt,
                'options' => $q->options,
                'correct_option_index' => $q->correct_option_index,
            ])->values(),
        ]);
    }

    public function update(Request $request, Quiz $quiz): JsonResponse|RedirectResponse
    {
        $this->authorizeCreator($request, $quiz);

        if (! $quiz->isDraft()) {
            return response()->json(['error' => 'Only drafts can be edited'], 422);
        }

        $data = $request->validate([
            'title' => 'nullable|string|max:80',
            'description' => 'nullable|string|max:500',
            'mode' => 'nullable|in:strict,casual',
            'seconds_per_question' => 'nullable|integer|min:2|max:10',
        ]);

        $this->quizService->updateDraft($quiz, $data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('studio.quizzes.edit', $quiz->ulid)->with('success', 'Quiz saved.');
    }

    public function destroy(Request $request, Quiz $quiz): RedirectResponse|JsonResponse
    {
        $this->authorizeCreator($request, $quiz);

        try {
            $this->quizService->deleteDraft($quiz);
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('studio')->with('success', 'Quiz deleted.');
    }

    public function publish(Request $request, Quiz $quiz): JsonResponse
    {
        $this->authorizeCreator($request, $quiz);

        $data = $request->validate([
            'is_listed' => 'required|boolean',
        ]);

        try {
            $this->quizService->publish($quiz, $data['is_listed']);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        return response()->json(['success' => true, 'status' => 'published']);
    }

    public function unpublish(Request $request, Quiz $quiz): JsonResponse
    {
        $this->authorizeCreator($request, $quiz);

        if (! $quiz->isPublished()) {
            return response()->json(['error' => 'Quiz is not published'], 422);
        }

        $this->quizService->unpublish($quiz);

        return response()->json(['success' => true, 'status' => 'unpublished']);
    }

    public function duplicate(Request $request, Quiz $quiz): JsonResponse
    {
        $this->authorizeCreator($request, $quiz);

        $newQuiz = $this->quizService->duplicate($quiz, $request->user());

        return response()->json([
            'success' => true,
            'ulid' => $newQuiz->ulid,
            'redirect' => route('studio.quizzes.edit', $newQuiz->ulid),
        ]);
    }

    public function stats(Request $request, Quiz $quiz): Response
    {
        $this->authorizeCreator($request, $quiz);

        $quiz->loadCount('questions');

        // Per-question stats
        $questionStats = [];
        foreach ($quiz->questions as $question) {
            $answers = RunAnswer::where('question_id', $question->id)
                ->whereHas('run', function ($query) use ($quiz) {
                    $query->whereIn('status', ['passed', 'failed'])
                        ->where(function ($query) use ($quiz) {
                            $query->whereNull('user_id')
                                ->orWhere('user_id', '!=', $quiz->user_id);
                        });
                })
                ->get();

            $total = $answers->count();
            $correct = $answers->where('is_correct', true)->count();
            $timeouts = $answers->where('failure_reason', 'timeout')->count();
            $focusLost = $answers->where('failure_reason', 'focus_lost')->count();
            $wrongAnswers = $answers->where('failure_reason', 'wrong_answer')->count();
            $avgTime = $total > 0 ? round($answers->avg('time_ms')) : null;

            // Most-picked wrong option
            $wrongPicks = $answers->whereNotNull('chosen_option_index')
                ->where('is_correct', false)
                ->where('failure_reason', 'wrong_answer')
                ->groupBy('chosen_option_index')
                ->map->count()
                ->sortDesc();

            $mostPickedWrong = $wrongPicks->keys()->first();
            $mostPickedWrongText = $mostPickedWrong !== null ? ($question->options[$mostPickedWrong] ?? null) : null;

            $questionStats[] = [
                'id' => $question->id,
                'position' => $question->position,
                'prompt' => $question->prompt,
                'total_answers' => $total,
                'correct_rate' => $total > 0 ? round($correct / $total * 100, 1) : null,
                'avg_time_ms' => $avgTime,
                'timeout_count' => $timeouts,
                'wrong_count' => $wrongAnswers,
                'focus_lost_count' => $focusLost,
                'most_picked_wrong' => $mostPickedWrongText,
            ];
        }

        // Per-player stats (non-guest)
        $playerStats = QuizUserStat::with('user:id,name,avatar_url')
            ->where('quiz_id', $quiz->id)
            ->where('user_id', '!=', $quiz->user_id)
            ->orderByDesc('last_played_at')
            ->get()
            ->map(fn ($s) => [
                'user_id' => $s->user_id,
                'name' => $s->user->name ?? 'Unknown',
                'avatar_url' => $s->user->avatar_url ?? null,
                'attempts' => $s->attempts,
                'best_reached' => $s->best_reached,
                'passed' => $s->passed,
                'first_passed_at' => $s->first_passed_at,
                'last_played_at' => $s->last_played_at,
            ]);

        // Guest stats aggregate
        $guestRuns = QuizRun::where('quiz_id', $quiz->id)
            ->whereNull('user_id')
            ->whereNotNull('guest_token')
            ->whereIn('status', ['passed', 'failed'])
            ->get();

        $guestStats = [
            'attempts' => $guestRuns->count(),
            'passes' => $guestRuns->where('status', 'passed')->count(),
            'best_reached' => $guestRuns->max('reached') ?? 0,
        ];

        // Unique players
        $uniqueUsers = QuizUserStat::where('quiz_id', $quiz->id)
            ->where('user_id', '!=', $quiz->user_id)
            ->distinct('user_id')
            ->count('user_id');
        $uniqueGuests = QuizRun::where('quiz_id', $quiz->id)
            ->whereNull('user_id')
            ->whereNotNull('guest_token')
            ->whereIn('status', ['passed', 'failed'])
            ->distinct('guest_token')
            ->count('guest_token');

        return Inertia::render('Studio/Stats', [
            'quiz' => [
                'id' => $quiz->id,
                'ulid' => $quiz->ulid,
                'title' => $quiz->title,
                'mode' => $quiz->mode,
                'runs_count' => $quiz->runs_count,
                'passes_count' => $quiz->passes_count,
                'questions_count' => $quiz->questions_count,
            ],
            'questionStats' => $questionStats,
            'playerStats' => $playerStats,
            'guestStats' => $guestStats,
            'uniquePlayers' => $uniqueUsers + $uniqueGuests,
            'passRate' => $quiz->runs_count > 0
                ? round($quiz->passes_count / $quiz->runs_count * 100, 1)
                : null,
        ]);
    }

    // Question CRUD endpoints
    public function storeQuestion(Request $request, Quiz $quiz): JsonResponse
    {
        $this->authorizeCreator($request, $quiz);

        if (! $quiz->isDraft()) {
            return response()->json(['error' => 'Only drafts can be edited'], 422);
        }

        $data = $this->validateQuestionData($request);

        $question = $this->quizService->upsertQuestion($quiz, $data);

        return response()->json([
            'question' => [
                'id' => $question->id,
                'position' => $question->position,
                'prompt' => $question->prompt,
                'options' => $question->options,
                'correct_option_index' => $question->correct_option_index,
            ],
        ], 201);
    }

    public function updateQuestion(Request $request, Quiz $quiz, int $questionId): JsonResponse
    {
        $this->authorizeCreator($request, $quiz);

        if (! $quiz->isDraft()) {
            return response()->json(['error' => 'Only drafts can be edited'], 422);
        }

        $data = $this->validateQuestionData($request);

        $question = $this->quizService->upsertQuestion($quiz, $data, $questionId);

        return response()->json([
            'question' => [
                'id' => $question->id,
                'position' => $question->position,
                'prompt' => $question->prompt,
                'options' => $question->options,
                'correct_option_index' => $question->correct_option_index,
            ],
        ]);
    }

    public function destroyQuestion(Request $request, Quiz $quiz, int $questionId): JsonResponse
    {
        $this->authorizeCreator($request, $quiz);

        if (! $quiz->isDraft()) {
            return response()->json(['error' => 'Only drafts can be edited'], 422);
        }

        $this->quizService->deleteQuestion($quiz, $questionId);

        return response()->json(['success' => true]);
    }

    private function authorizeCreator(Request $request, Quiz $quiz): void
    {
        $user = $request->user();
        if (! $user || $user->id !== $quiz->user_id) {
            abort(403);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateQuestionData(Request $request): array
    {
        return $request->validate([
            'prompt' => 'nullable|string|max:200',
            'options' => 'nullable|array|max:4',
            'options.*' => 'nullable|string|max:80',
            'correct_option_index' => 'nullable|integer|min:0|max:3',
            'position' => 'nullable|integer|min:0',
        ]);
    }
}
