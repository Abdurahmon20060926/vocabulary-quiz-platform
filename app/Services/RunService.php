<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizRun;
use App\Models\QuizUserStat;
use App\Models\RunAnswer;
use Illuminate\Support\Facades\DB;

class RunService
{
    private float $gracePeriod;

    public function __construct()
    {
        $this->gracePeriod = (float) config('quiz.grace_seconds', 1.5);
    }

    public function startRun(Quiz $quiz, ?int $userId, ?string $guestToken): array
    {
        $questions = $quiz->questions()->get();

        if (! in_array($quiz->mode, ['strict', 'casual'], true)) {
            throw new \DomainException('Choose a quiz mode before test playing this draft.');
        }

        if ($quiz->seconds_per_question === null || $quiz->seconds_per_question < 2 || $quiz->seconds_per_question > 10) {
            throw new \DomainException('Choose a timer between 2 and 10 seconds before test playing this draft.');
        }

        if ($questions->isEmpty()) {
            throw new \DomainException('Add at least one complete question before test playing this draft.');
        }

        foreach ($questions as $question) {
            if (
                trim($question->prompt) === ''
                || count($question->options) !== 4
                || collect($question->options)->contains(fn ($option) => ! is_string($option) || trim($option) === '')
                || $question->correct_option_index === null
                || $question->correct_option_index < 0
                || $question->correct_option_index > 3
            ) {
                throw new \DomainException('Complete every question before test playing this draft.');
            }
        }

        $questionIds = $questions->pluck('id')->toArray();
        shuffle($questionIds);

        return DB::transaction(function () use ($quiz, $userId, $guestToken, $questions, $questionIds) {
            // Abandon any existing in-progress run for this player on this quiz
            $this->abandonExistingRuns($quiz->id, $userId, $guestToken);

            if ($quiz->mode === 'strict') {
                return $this->startStrictRun($quiz, $userId, $guestToken, $questions, $questionIds);
            } else {
                return $this->startCasualRun($quiz, $userId, $guestToken, $questions, $questionIds);
            }
        });
    }

    private function startStrictRun(Quiz $quiz, ?int $userId, ?string $guestToken, $questions, array $questionIds): array
    {
        // Build option orders (permutation of 0-3 for each question)
        $optionOrders = [];
        foreach ($questionIds as $qid) {
            $perm = [0, 1, 2, 3];
            shuffle($perm);
            $optionOrders[$qid] = $perm;
        }

        $run = QuizRun::create([
            'quiz_id' => $quiz->id,
            'user_id' => $userId,
            'guest_token' => $guestToken,
            'mode' => 'strict',
            'question_order' => $questionIds,
            'option_orders' => $optionOrders,
            'current_index' => 0,
            'current_issued_at' => now(),
            'status' => 'in_progress',
        ]);

        $firstQid = $questionIds[0];
        $firstQ = $questions->firstWhere('id', $firstQid);
        $perm = $optionOrders[$firstQid];
        $displayOptions = array_map(fn ($i) => $firstQ->options[$i], $perm);

        return [
            'run' => $run,
            'question' => [
                'id' => $firstQ->id,
                'prompt' => $firstQ->prompt,
                'options' => $displayOptions,
                'index' => 0,
                'total' => count($questionIds),
            ],
        ];
    }

    private function startCasualRun(Quiz $quiz, ?int $userId, ?string $guestToken, $questions, array $questionIds): array
    {
        $salt = bin2hex(random_bytes(16));

        $run = QuizRun::create([
            'quiz_id' => $quiz->id,
            'user_id' => $userId,
            'guest_token' => $guestToken,
            'mode' => 'casual',
            'question_order' => $questionIds,
            'salt' => $salt,
            'status' => 'in_progress',
        ]);

        $questionsData = [];
        foreach ($questions as $q) {
            $correctText = $q->options[$q->correct_option_index];
            $hash = hash('sha256', $salt.':'.$q->id.':'.$correctText);
            $questionsData[] = [
                'id' => $q->id,
                'prompt' => $q->prompt,
                'options' => $q->options, // original order; client shuffles
                'answer_hash' => $hash,
            ];
        }

        return [
            'run' => $run,
            'questions' => $questionsData,
            'salt' => $salt,
        ];
    }

    public function submitStrictAnswer(QuizRun $run, array $data): array
    {
        return DB::transaction(function () use ($run, $data) {
            $run = QuizRun::query()->lockForUpdate()->findOrFail($run->id);

            if (! $run->isInProgress()) {
                return ['error' => 'Run is not in progress.'];
            }

            $now = now();
            $issuedAt = $run->current_issued_at;
            $quiz = $run->quiz;
            $nowMicroseconds = ((int) $now->format('U') * 1_000_000) + (int) $now->format('u');
            $issuedAtMicroseconds = $issuedAt
                ? ((int) $issuedAt->format('U') * 1_000_000) + (int) $issuedAt->format('u')
                : $nowMicroseconds;
            $elapsedMicroseconds = max(0, $nowMicroseconds - $issuedAtMicroseconds);
            $elapsedMs = intdiv($elapsedMicroseconds, 1_000);
            $timeLimitMicroseconds = (int) round(($quiz->seconds_per_question + $this->gracePeriod) * 1_000_000);
            $reason = $data['reason'] ?? null;

            if ($reason === 'focus_lost') {
                return $this->finishRun($run, 'failed', 'focus_lost', null, $elapsedMs);
            }

            if ($elapsedMicroseconds > $timeLimitMicroseconds || $reason === 'timeout') {
                return $this->finishRun($run, 'failed', 'timeout', null, $elapsedMs);
            }

            $displayedIndex = $data['displayed_index'] ?? null;
            if (! is_int($displayedIndex) || $displayedIndex < 0 || $displayedIndex > 3) {
                return ['error' => 'A valid displayed_index is required.'];
            }

            $questionIds = $run->question_order;
            $currentQid = $questionIds[$run->current_index] ?? null;
            if ($currentQid === null) {
                return ['error' => 'Run question state is invalid.'];
            }

            $optionOrders = $run->option_orders;
            $perm = $optionOrders[$currentQid] ?? null;
            $originalIndex = is_array($perm) ? ($perm[$displayedIndex] ?? null) : null;
            if (! is_int($originalIndex) || $originalIndex < 0 || $originalIndex > 3) {
                return ['error' => 'Run option state is invalid.'];
            }

            $question = Question::find($currentQid);
            $isCorrect = $question && $originalIndex === $question->correct_option_index;

            if (! $isCorrect) {
                return $this->finishRun($run, 'failed', 'wrong_answer', $originalIndex, $elapsedMs);
            }

            RunAnswer::create([
                'quiz_run_id' => $run->id,
                'question_id' => $currentQid,
                'chosen_option_index' => $originalIndex,
                'is_correct' => true,
                'failure_reason' => null,
                'time_ms' => $elapsedMs,
                'created_at' => now(),
            ]);
            $run->increment('reached');

            $totalQuestions = count($questionIds);
            if (($run->current_index + 1) >= $totalQuestions) {
                return $this->finishRunPassed($run);
            }

            $nextIndex = $run->current_index + 1;
            $nextQid = $questionIds[$nextIndex];
            $nextPerm = $optionOrders[$nextQid] ?? [0, 1, 2, 3];
            $nextQ = Question::find($nextQid);
            $nextDisplayOptions = array_map(fn ($i) => $nextQ->options[$i], $nextPerm);

            $run->update([
                'current_index' => $nextIndex,
                'current_issued_at' => now(),
            ]);

            return [
                'verdict' => 'correct',
                'next_question' => [
                    'id' => $nextQ->id,
                    'prompt' => $nextQ->prompt,
                    'options' => $nextDisplayOptions,
                    'index' => $nextIndex,
                    'total' => $totalQuestions,
                ],
            ];
        });
    }

    public function submitCasualRun(QuizRun $run, array $answers): array
    {
        return DB::transaction(function () use ($run, $answers) {
            $run = QuizRun::query()->lockForUpdate()->findOrFail($run->id);

            if (! $run->isInProgress()) {
                return ['error' => 'Run is not in progress.'];
            }

            $questionOrder = array_map('intval', $run->question_order ?? []);
            $totalQuestions = count($questionOrder);

            if (! array_is_list($answers) || count($answers) < $totalQuestions) {
                return ['error' => 'The answer log is incomplete.'];
            }
            if (
                count($answers) !== $totalQuestions
                || ! array_is_list($questionOrder)
                || count(array_unique($questionOrder)) !== $totalQuestions
            ) {
                return ['error' => 'The answer log is invalid.'];
            }

            $questions = $run->quiz->questions()->get()->keyBy('id');
            $expectedQuestionIds = array_fill_keys($questionOrder, true);
            $seenQuestionIds = [];
            $normalizedAnswers = [];

            foreach ($answers as $entry) {
                if (
                    ! is_array($entry)
                    || array_diff(array_keys($entry), ['question_id', 'chosen_option_index', 'reason', 'time_ms']) !== []
                    || ! array_key_exists('question_id', $entry)
                    || ! array_key_exists('chosen_option_index', $entry)
                    || ! array_key_exists('time_ms', $entry)
                ) {
                    return ['error' => 'The answer log is invalid.'];
                }

                $questionId = $entry['question_id'];
                $chosenIndex = $entry['chosen_option_index'];
                $clientReason = $entry['reason'] ?? null;
                $timeMs = $entry['time_ms'];

                if (
                    ! is_int($questionId)
                    || ! isset($expectedQuestionIds[$questionId])
                    || isset($seenQuestionIds[$questionId])
                    || ! $questions->has($questionId)
                    || ($chosenIndex !== null && (! is_int($chosenIndex) || $chosenIndex < 0 || $chosenIndex > 3))
                    || ! in_array($clientReason, [null, 'timeout', 'focus_lost'], true)
                    || ! is_int($timeMs)
                    || $timeMs < 0
                    || ($clientReason !== null && $chosenIndex !== null)
                ) {
                    return ['error' => 'The answer log is invalid.'];
                }

                $seenQuestionIds[$questionId] = true;
                $normalizedAnswers[] = [$questionId, $chosenIndex, $clientReason, $timeMs];
            }

            if (count($seenQuestionIds) !== $totalQuestions) {
                return ['error' => 'The answer log is incomplete.'];
            }

            $reached = 0;
            $finalStatus = 'passed';
            $finalReason = null;
            $failedAtAnswer = null;
            $runAnswersData = [];
            $terminalAnswerSeen = false;

            foreach ($normalizedAnswers as [$questionId, $chosenIndex, $clientReason, $timeMs]) {
                if ($terminalAnswerSeen) {
                    if ($chosenIndex !== null || $clientReason !== null || $timeMs !== 0) {
                        return ['error' => 'The answer log is invalid.'];
                    }

                    continue;
                }

                if ($chosenIndex === null && $clientReason === null) {
                    return ['error' => 'The answer log is invalid.'];
                }

                if ($clientReason !== null) {
                    $finalStatus = 'failed';
                    $finalReason = $clientReason;
                    $failedAtAnswer = [
                        'quiz_run_id' => $run->id,
                        'question_id' => $questionId,
                        'chosen_option_index' => null,
                        'is_correct' => false,
                        'failure_reason' => $clientReason,
                        'time_ms' => $timeMs,
                        'created_at' => now(),
                    ];
                    $terminalAnswerSeen = true;

                    continue;
                }

                $question = $questions->get($questionId);
                $isCorrect = $chosenIndex === $question->correct_option_index;

                $runAnswersData[] = [
                    'quiz_run_id' => $run->id,
                    'question_id' => $questionId,
                    'chosen_option_index' => $chosenIndex,
                    'is_correct' => $isCorrect,
                    'failure_reason' => $isCorrect ? null : 'wrong_answer',
                    'time_ms' => $timeMs,
                    'created_at' => now(),
                ];

                if (! $isCorrect) {
                    $finalStatus = 'failed';
                    $finalReason = 'wrong_answer';
                    $terminalAnswerSeen = true;

                    continue;
                }

                $reached++;
            }

            if ($finalStatus === 'passed' && $reached !== $totalQuestions) {
                return ['error' => 'The answer log is incomplete.'];
            }

            foreach ($runAnswersData as $answerData) {
                RunAnswer::create($answerData);
            }
            if ($failedAtAnswer) {
                RunAnswer::create($failedAtAnswer);
            }

            $run->update(['reached' => $reached]);

            return $this->bookkeepRunEnd($run, $finalStatus, $finalReason);
        });
    }

    private function finishRun(QuizRun $run, string $status, string $reason, ?int $chosenIndex, int $timeMs): array
    {
        return DB::transaction(function () use ($run, $status, $reason, $chosenIndex, $timeMs) {
            $questionIds = $run->question_order;
            $currentQid = $questionIds[$run->current_index] ?? null;

            if ($currentQid) {
                RunAnswer::create([
                    'quiz_run_id' => $run->id,
                    'question_id' => $currentQid,
                    'chosen_option_index' => $chosenIndex,
                    'is_correct' => false,
                    'failure_reason' => $reason,
                    'time_ms' => $timeMs,
                    'created_at' => now(),
                ]);
            }

            return $this->bookkeepRunEnd($run, $status, $reason);
        });
    }

    private function finishRunPassed(QuizRun $run): array
    {
        return DB::transaction(function () use ($run) {
            return $this->bookkeepRunEnd($run, 'passed', null);
        });
    }

    private function bookkeepRunEnd(QuizRun $run, string $status, ?string $reason): array
    {
        $run->refresh();
        $run->update([
            'status' => $status,
            'failure_reason' => $reason,
            'finished_at' => now(),
        ]);

        $quiz = $run->quiz;
        $isCreator = $run->user_id && $run->user_id === $quiz->user_id;

        if (! $isCreator) {
            DB::table('quizzes')->where('id', $quiz->id)->update([
                'runs_count' => DB::raw('runs_count + 1'),
                'reached_sum' => DB::raw("reached_sum + {$run->reached}"),
                'passes_count' => $status === 'passed'
                    ? DB::raw('passes_count + 1')
                    : DB::raw('passes_count'),
            ]);
        }

        // Upsert user stats for logged-in users
        if ($run->user_id) {
            $existing = QuizUserStat::firstOrNew([
                'user_id' => $run->user_id,
                'quiz_id' => $quiz->id,
            ]);
            $existing->attempts = ($existing->attempts ?? 0) + 1;
            $existing->best_reached = max($existing->best_reached ?? 0, $run->reached);
            if ($status === 'passed' && ! $existing->passed) {
                $existing->passed = true;
                $existing->first_passed_at = now();
            }
            $existing->last_played_at = now();
            $existing->save();
        }

        $run->refresh();
        $quiz->refresh();

        return [
            'verdict' => $status,
            'failure_reason' => $reason,
            'reached' => $run->reached,
            'total' => $run->totalQuestions(),
        ];
    }

    private function abandonExistingRuns(int $quizId, ?int $userId, ?string $guestToken): void
    {
        $query = QuizRun::where('quiz_id', $quizId)->where('status', 'in_progress');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($guestToken) {
            $query->where('guest_token', $guestToken);
        } else {
            return;
        }

        $query->update(['status' => 'abandoned', 'finished_at' => now()]);
    }

    public function getUserAttemptInfo(int $quizId, ?int $userId): ?array
    {
        if (! $userId) {
            return null;
        }

        $stat = QuizUserStat::where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->first();

        if (! $stat) {
            return null;
        }

        return [
            'attempts' => $stat->attempts,
            'best_reached' => $stat->best_reached,
            'passed' => $stat->passed,
            'first_passed_at' => $stat->first_passed_at,
        ];
    }
}
