<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizRun;
use App\Services\RunService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RunController extends Controller
{
    public function __construct(private RunService $runService) {}

    public function start(Request $request, Quiz $quiz): JsonResponse
    {
        $isCreator = $request->user()?->id === $quiz->user_id;
        $canPlay = $quiz->isPublished()
            || ($isCreator && ($quiz->isDraft() || $quiz->isUnpublished()));

        if (! $canPlay) {
            return response()->json(['error' => 'Quiz not available'], 404);
        }

        $user = $request->user();
        $userId = $user?->id;
        $guestToken = $userId ? null : $this->getOrCreateGuestToken($request);

        try {
            $result = $this->runService->startRun($quiz, $userId, $guestToken);
        } catch (\DomainException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        $response = [
            'run_id' => $result['run']->id,
            'mode' => $quiz->mode,
        ];

        if ($quiz->mode === 'strict') {
            $response['question'] = $result['question'];
        } else {
            $response['questions'] = $result['questions'];
            $response['salt'] = $result['salt'];
        }

        $headers = [];
        if (! $userId && isset($guestToken)) {
            $headers['X-Guest-Token'] = $guestToken;
        }

        return response()->json($response, 200, $headers);
    }

    public function answer(Request $request, string $runId): JsonResponse
    {
        $run = QuizRun::find($runId);

        if (! $run) {
            return response()->json(['error' => 'Run not found'], 404);
        }

        if (! $this->authorizeRun($request, $run)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($run->mode !== 'strict') {
            return response()->json(['error' => 'Use /submit for casual runs'], 422);
        }

        if ($run->isInProgress() && $run->quiz->isUnpublished() && $run->user_id !== $run->quiz->user_id) {
            $run->update(['status' => 'abandoned', 'finished_at' => now()]);

            return response()->json(['verdict' => 'abandoned', 'reason' => 'quiz_unavailable']);
        }

        $hasDisplayedIndex = $request->exists('displayed_index');
        $hasReason = $request->exists('reason');

        if ($hasDisplayedIndex === $hasReason) {
            return response()->json(['error' => 'Provide exactly one displayed index or failure reason.'], 422);
        }

        $data = $request->validate($hasDisplayedIndex
            ? [
                'displayed_index' => ['required', 'integer', 'between:0,3'],
                'reason' => ['prohibited'],
            ]
            : [
                'displayed_index' => ['prohibited'],
                'reason' => ['required', 'in:timeout,focus_lost'],
            ]);

        if ($hasDisplayedIndex) {
            $data['displayed_index'] = (int) $data['displayed_index'];
        }

        $result = $this->runService->submitStrictAnswer($run, $data);

        return response()->json($result, isset($result['error']) ? 422 : 200);
    }

    public function submit(Request $request, string $runId): JsonResponse
    {
        $run = QuizRun::find($runId);

        if (! $run) {
            return response()->json(['error' => 'Run not found'], 404);
        }

        if (! $this->authorizeRun($request, $run)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($run->mode !== 'casual') {
            return response()->json(['error' => 'Use /answers for strict runs'], 422);
        }

        if ($run->isInProgress() && $run->quiz->isUnpublished() && $run->user_id !== $run->quiz->user_id) {
            $run->update(['status' => 'abandoned', 'finished_at' => now()]);

            return response()->json(['verdict' => 'abandoned', 'reason' => 'quiz_unavailable']);
        }

        $answers = $request->input('answers', []);
        if (! is_array($answers)) {
            return response()->json(['error' => 'Answers must be an array.'], 422);
        }

        $result = $this->runService->submitCasualRun($run, $answers);

        return response()->json($result, isset($result['error']) ? 422 : 200);
    }

    private function authorizeRun(Request $request, QuizRun $run): bool
    {
        $user = $request->user();

        if ($user && $run->user_id === $user->id) {
            return true;
        }

        if (! $user && $run->guest_token) {
            $cookieToken = $request->cookie('guest_token');
            $headerToken = $request->header('X-Guest-Token');
            $token = $cookieToken ?? $headerToken;

            return $token && $token === $run->guest_token;
        }

        return false;
    }

    private function getOrCreateGuestToken(Request $request): string
    {
        $token = $request->cookie('guest_token') ?: $request->attributes->get('guest_token');
        if (! $token) {
            $token = bin2hex(random_bytes(32));
        }

        return $token;
    }
}
