<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizService
{
    public function createDraft(User $user, array $data): Quiz
    {
        return $user->quizzes()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'mode' => $data['mode'] ?? 'casual',
            'seconds_per_question' => $data['seconds_per_question'] ?? 10,
            'status' => 'draft',
            'is_listed' => true,
        ]);
    }

    public function updateDraft(Quiz $quiz, array $data): Quiz
    {
        $quiz->update([
            'title' => $data['title'] ?? $quiz->title,
            'description' => $data['description'] ?? $quiz->description,
            'mode' => $data['mode'] ?? $quiz->mode,
            'seconds_per_question' => $data['seconds_per_question'] ?? $quiz->seconds_per_question,
        ]);
        return $quiz->fresh();
    }

    public function publish(Quiz $quiz, bool $isListed): Quiz
    {
        $this->validateForPublish($quiz);

        DB::transaction(function () use ($quiz, $isListed) {
            $quiz->update([
                'status' => 'published',
                'is_listed' => $isListed,
                'published_at' => $quiz->published_at ?? now(),
            ]);
        });

        return $quiz->fresh();
    }

    public function unpublish(Quiz $quiz): Quiz
    {
        DB::transaction(function () use ($quiz) {
            $quiz->update(['status' => 'unpublished', 'is_listed' => false]);
            // Abandon in-progress runs by other players
            $quiz->runs()
                ->where('status', 'in_progress')
                ->where(function ($q) use ($quiz) {
                    $q->whereNull('user_id')
                      ->orWhere('user_id', '!=', $quiz->user_id);
                })
                ->update(['status' => 'abandoned', 'finished_at' => now()]);
        });

        return $quiz->fresh();
    }

    public function duplicate(Quiz $quiz, User $user): Quiz
    {
        return DB::transaction(function () use ($quiz, $user) {
            $newQuiz = $user->quizzes()->create([
                'title' => $quiz->title . ' (copy)',
                'description' => $quiz->description,
                'mode' => $quiz->mode,
                'seconds_per_question' => $quiz->seconds_per_question,
                'status' => 'draft',
                'is_listed' => true,
            ]);

            foreach ($quiz->questions as $question) {
                $newQuiz->questions()->create([
                    'position' => $question->position,
                    'prompt' => $question->prompt,
                    'options' => $question->options,
                    'correct_option_index' => $question->correct_option_index,
                ]);
            }

            return $newQuiz;
        });
    }

    public function deleteDraft(Quiz $quiz): void
    {
        if (!$quiz->isDraft()) {
            throw new \RuntimeException('Only drafts can be deleted.');
        }
        $quiz->delete();
    }

    public function validateForPublish(Quiz $quiz): void
    {
        $questions = $quiz->questions;
        $count = $questions->count();

        if ($count < 5 || $count > 15) {
            throw ValidationException::withMessages([
                'questions' => "A quiz must have between 5 and 15 questions (currently {$count}).",
            ]);
        }

        if (!in_array($quiz->mode, ['strict', 'casual'])) {
            throw ValidationException::withMessages([
                'mode' => 'Quiz mode must be strict or casual.',
            ]);
        }

        if ($quiz->seconds_per_question < 2 || $quiz->seconds_per_question > 10) {
            throw ValidationException::withMessages([
                'seconds_per_question' => 'Timer must be between 2 and 10 seconds.',
            ]);
        }

        foreach ($questions as $i => $question) {
            $num = $i + 1;

            if (empty(trim($question->prompt))) {
                throw ValidationException::withMessages([
                    'questions' => "Question {$num} has an empty prompt.",
                ]);
            }

            $options = $question->options;
            if (!is_array($options) || count($options) !== 4) {
                throw ValidationException::withMessages([
                    'questions' => "Question {$num} must have exactly 4 options.",
                ]);
            }

            foreach ($options as $j => $opt) {
                if (empty(trim($opt))) {
                    throw ValidationException::withMessages([
                        'questions' => "Question {$num}, option " . ($j + 1) . " is empty.",
                    ]);
                }
            }

            $normalized = array_map(fn($o) => strtolower(trim($o)), $options);
            if (count(array_unique($normalized)) !== 4) {
                throw ValidationException::withMessages([
                    'questions' => "Question {$num} has duplicate options.",
                ]);
            }

            if ($question->correct_option_index < 0 || $question->correct_option_index > 3) {
                throw ValidationException::withMessages([
                    'questions' => "Question {$num} has an invalid correct answer.",
                ]);
            }
        }
    }

    public function upsertQuestion(Quiz $quiz, array $data, ?int $questionId = null): Question
    {
        if ($questionId) {
            $question = $quiz->questions()->findOrFail($questionId);
            $question->update([
                'prompt' => $data['prompt'],
                'options' => $data['options'],
                'correct_option_index' => $data['correct_option_index'],
                'position' => $data['position'] ?? $question->position,
            ]);
            return $question;
        }

        $maxPos = $quiz->questions()->max('position') ?? 0;
        return $quiz->questions()->create([
            'prompt' => $data['prompt'],
            'options' => $data['options'],
            'correct_option_index' => $data['correct_option_index'],
            'position' => $data['position'] ?? ($maxPos + 1),
        ]);
    }

    public function deleteQuestion(Quiz $quiz, int $questionId): void
    {
        $question = $quiz->questions()->findOrFail($questionId);
        $question->delete();
    }
}
