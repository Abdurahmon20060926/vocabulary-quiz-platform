<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\RunAnswer;
use App\Models\QuizRun;
use App\Models\QuizUserStat;
use App\Services\QuizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->map(fn($q) => [
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

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:80',
            'description' => 'nullable|string|max:500',
            'mode' => 'required|in:strict,casual',
            'seconds_per_question' => 'required|integer|min:2|max:10',
        ]);

        $quiz = $this->quizService->createDraft($request->user(), $data);

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
            'questions' => $quiz->questions->map(fn($q) => [
                'id' => $q->id,
                'position' => $q->position,
                'prompt' => $q->prompt,
                'options' => $q->options,
                'correct_option_index' => $q->correct_option_index,
            ])->values(),
        ]);
    }

    public function update(Request $request, Quiz $quiz): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $this->authorizeCreator($request, $quiz);

        if (!$quiz->isDraft()) {
            return response()->json(['error' => 'Only drafts can be edited'], 422);
        }

        $data = $request->validate([
            'title' => 'required|string|max:80',
            'description' => 'nullable|string|max:500',
            'mode' => 'required|in:strict,casual',
            'seconds_per_question' => 'required|integer|min:2|max:10',
        ]);

        $this->quizService->updateDraft($quiz, $data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('studio.quizzes.edit', $quiz->ulid)->with('success', 'Quiz saved.');
    }

    public function destroy(Request $request, Quiz $quiz): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
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

    public function publish(Request $request, Quiz $quiz): \Illuminate\Http\JsonResponse
    {
        $this->authorizeCreator($request, $quiz);

        $data = $request->validate([
            'is_listed' => 'required|boolean',
        ]);

        try {
            $this->quizService->publish($quiz, $data['is_listed']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        return response()->json(['success' => true, 'status' => 'published']);
    }

    public function unpublish(Request $request, Quiz $quiz): \Illuminate\Http\JsonResponse
    {
        $this->authorizeCreator($request, $quiz);

        if (!$quiz->isPublished()) {
            return response()->json(['error' => 'Quiz is not published'], 422);
        }

        $this->quizService->unpublish($quiz);

        return response()->json(['success' => true, 'status' => 'unpublished']);
    }

    public function duplicate(Request $request, Quiz $quiz): \Illuminate\Http\JsonResponse
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
                ->whereHas('run', fn($q) => $q->where('status', '!=', 'in_progress'))
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
            ->orderByDesc('last_played_at')
            ->get()
            ->map(fn($s) => [
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
            ->where('status', '!=', 'in_progress')
            ->get();

        $guestStats = [
            'attempts' => $guestRuns->count(),
            'passes' => $guestRuns->where('status', 'passed')->count(),
            'best_reached' => $guestRuns->max('reached') ?? 0,
        ];

        // Unique players
        $uniqueUsers = QuizUserStat::where('quiz_id', $quiz->id)->distinct('user_id')->count('user_id');
        $uniqueGuests = QuizRun::where('quiz_id', $quiz->id)
            ->whereNull('user_id')
            ->whereNotNull('guest_token')
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
    public function storeQuestion(Request $request, Quiz $quiz): \Illuminate\Http\JsonResponse
    {
        $this->authorizeCreator($request, $quiz);

        if (!$quiz->isDraft()) {
            return response()->json(['error' => 'Only drafts can be edited'], 422);
        }

        $data = $request->validate([
            'prompt' => 'required|string|max:200',
            'options' => 'required|array|size:4',
            'options.*' => 'required|string|max:80',
            'correct_option_index' => 'required|integer|min:0|max:3',
            'position' => 'nullable|integer|min:0',
        ]);

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

    public function updateQuestion(Request $request, Quiz $quiz, int $questionId): \Illuminate\Http\JsonResponse
    {
        $this->authorizeCreator($request, $quiz);

        if (!$quiz->isDraft()) {
            return response()->json(['error' => 'Only drafts can be edited'], 422);
        }

        $data = $request->validate([
            'prompt' => 'required|string|max:200',
            'options' => 'required|array|size:4',
            'options.*' => 'required|string|max:80',
            'correct_option_index' => 'required|integer|min:0|max:3',
            'position' => 'nullable|integer|min:0',
        ]);

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

    public function destroyQuestion(Request $request, Quiz $quiz, int $questionId): \Illuminate\Http\JsonResponse
    {
        $this->authorizeCreator($request, $quiz);

        if (!$quiz->isDraft()) {
            return response()->json(['error' => 'Only drafts can be edited'], 422);
        }

        $this->quizService->deleteQuestion($quiz, $questionId);

        return response()->json(['success' => true]);
    }

    private function authorizeCreator(Request $request, Quiz $quiz): void
    {
        $user = $request->user();
        if (!$user || $user->id !== $quiz->user_id) {
            abort(403);
        }
    }
}
