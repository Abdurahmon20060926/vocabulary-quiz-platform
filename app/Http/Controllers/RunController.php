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
        // Only published quizzes can be played (or drafts by creator for test play)
        if (!$quiz->isPublished()) {
            if ($quiz->isDraft()) {
                // Allow creator to test play their draft
                if (!$request->user() || $request->user()->id !== $quiz->user_id) {
                    return response()->json(['error' => 'Quiz not available'], 404);
                }
            } else {
                return response()->json(['error' => 'Quiz not available'], 404);
            }
        }

        $user = $request->user();
        $userId = $user?->id;
        $guestToken = $userId ? null : $this->getOrCreateGuestToken($request);

        try {
            $result = $this->runService->startRun($quiz, $userId, $guestToken);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
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
        if (!$userId && isset($guestToken)) {
            $headers['X-Guest-Token'] = $guestToken;
        }

        return response()->json($response, 200, $headers);
    }

    public function answer(Request $request, string $runId): JsonResponse
    {
        $run = QuizRun::find($runId);

        if (!$run) {
            return response()->json(['error' => 'Run not found'], 404);
        }

        if (!$this->authorizeRun($request, $run)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($run->mode !== 'strict') {
            return response()->json(['error' => 'Use /submit for casual runs'], 422);
        }

        // Check if quiz was unpublished
        if ($run->quiz->status === 'unpublished') {
            $run->update(['status' => 'abandoned', 'finished_at' => now()]);
            return response()->json(['verdict' => 'abandoned', 'reason' => 'quiz_unavailable']);
        }

        $data = $request->only(['displayed_index', 'reason']);
        $result = $this->runService->submitStrictAnswer($run, $data);

        return response()->json($result);
    }

    public function submit(Request $request, string $runId): JsonResponse
    {
        $run = QuizRun::find($runId);

        if (!$run) {
            return response()->json(['error' => 'Run not found'], 404);
        }

        if (!$this->authorizeRun($request, $run)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($run->mode !== 'casual') {
            return response()->json(['error' => 'Use /answers for strict runs'], 422);
        }

        // Check if quiz was unpublished
        if ($run->quiz->status === 'unpublished') {
            $run->update(['status' => 'abandoned', 'finished_at' => now()]);
            return response()->json(['verdict' => 'abandoned', 'reason' => 'quiz_unavailable']);
        }

        $answers = $request->input('answers', []);
        $result = $this->runService->submitCasualRun($run, $answers);

        return response()->json($result);
    }

    private function authorizeRun(Request $request, QuizRun $run): bool
    {
        $user = $request->user();

        if ($user && $run->user_id === $user->id) {
            return true;
        }

        if (!$user && $run->guest_token) {
            $cookieToken = $request->cookie('guest_token');
            $headerToken = $request->header('X-Guest-Token');
            $token = $cookieToken ?? $headerToken;
            return $token && $token === $run->guest_token;
        }

        return false;
    }

    private function getOrCreateGuestToken(Request $request): string
    {
        $token = $request->cookie('guest_token');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
        }
        // Set cookie in response via header - handled by middleware setting the cookie
        // Store in request for use
        $request->merge(['_guest_token_new' => $token]);
        return $token;
    }
}
