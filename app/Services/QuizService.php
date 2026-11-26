<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizService
{
    public function createDraft(User $user, array $data): Quiz
    {
        return $user->quizzes()->create([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'mode' => $data['mode'] ?? null,
            'seconds_per_question' => $data['seconds_per_question'] ?? null,
            'status' => 'draft',
            'is_listed' => true,
        ]);
    }

    public function updateDraft(Quiz $quiz, array $data): Quiz
    {
        $quiz->fill(array_intersect_key($data, array_flip([
            'title',
            'description',
            'mode',
            'seconds_per_question',
        ])))->save();

        return $quiz->fresh();
    }

    public function publish(Quiz $quiz, bool $isListed): Quiz
    {
        if ($quiz->isPublished()) {
            throw ValidationException::withMessages([
                'quiz' => 'Published quizzes are immutable. Duplicate this quiz to make changes.',
            ]);
        }

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
        });

        return $quiz->fresh();
    }

    public function duplicate(Quiz $quiz, User $user): Quiz
    {
        if (! $quiz->isPublished() && ! $quiz->isUnpublished()) {
            throw ValidationException::withMessages([
                'quiz' => 'Only published or unpublished quizzes can be duplicated.',
            ]);
        }

        return DB::transaction(function () use ($quiz, $user) {
            $copySuffix = ' (copy)';
            $sourceTitle = is_string($quiz->title) && trim($quiz->title) !== ''
                ? $quiz->title
                : 'Untitled';
            $copyTitle = rtrim(mb_substr($sourceTitle, 0, 80 - mb_strlen($copySuffix))).$copySuffix;

            $newQuiz = $user->quizzes()->create([
                'title' => $copyTitle,
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
        if (! $quiz->isDraft()) {
            throw new \RuntimeException('Only drafts can be deleted.');
        }
        $quiz->delete();
    }

    public function validateForPublish(Quiz $quiz): void
    {
        $questions = $quiz->questions;
        $count = $questions->count();

        if (! is_string($quiz->title) || trim($quiz->title) === '') {
            throw ValidationException::withMessages([
                'title' => 'A quiz title is required before publishing.',
            ]);
        }

        if ($count < 5 || $count > 15) {
            throw ValidationException::withMessages([
                'questions' => "A quiz must have between 5 and 15 questions (currently {$count}).",
            ]);
        }

        if (! in_array($quiz->mode, ['strict', 'casual'])) {
            throw ValidationException::withMessages([
                'mode' => 'Quiz mode must be strict or casual.',
            ]);
        }

        if ($quiz->seconds_per_question === null || $quiz->seconds_per_question < 2 || $quiz->seconds_per_question > 10) {
            throw ValidationException::withMessages([
                'seconds_per_question' => 'Timer must be between 2 and 10 seconds.',
            ]);
        }

        foreach ($questions as $i => $question) {
            $num = $i + 1;

            if (! is_string($question->prompt) || trim($question->prompt) === '') {
                throw ValidationException::withMessages([
                    'questions' => "Question {$num} has an empty prompt.",
                ]);
            }

            $options = $question->options;
            if (! is_array($options) || count($options) !== 4) {
                throw ValidationException::withMessages([
                    'questions' => "Question {$num} must have exactly 4 options.",
                ]);
            }

            foreach ($options as $j => $opt) {
                if (! is_string($opt) || trim($opt) === '') {
                    throw ValidationException::withMessages([
                        'questions' => "Question {$num}, option ".($j + 1).' is empty.',
                    ]);
                }
            }

            $normalized = array_map(fn ($option) => mb_strtolower(trim($option)), $options);
            if (count(array_unique($normalized)) !== 4) {
                throw ValidationException::withMessages([
                    'questions' => "Question {$num} has duplicate options.",
                ]);
            }

            if ($question->correct_option_index === null || $question->correct_option_index < 0 || $question->correct_option_index > 3) {
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
            $question->fill([
                'prompt' => array_key_exists('prompt', $data)
                    ? (string) ($data['prompt'] ?? '')
                    : $question->prompt,
                'options' => array_key_exists('options', $data)
                    ? $this->fourOptionSlots($data['options'] ?? [])
                    : $question->options,
                'correct_option_index' => array_key_exists('correct_option_index', $data)
                    ? $data['correct_option_index']
                    : $question->correct_option_index,
                'position' => $data['position'] ?? $question->position,
            ])->save();

            return $question;
        }

        $maxPos = $quiz->questions()->max('position') ?? 0;

        return $quiz->questions()->create([
            'prompt' => (string) ($data['prompt'] ?? ''),
            'options' => $this->fourOptionSlots($data['options'] ?? []),
            'correct_option_index' => $data['correct_option_index'] ?? null,
            'position' => $data['position'] ?? ($maxPos + 1),
        ]);
    }

    /**
     * @param  array<int, string|null>  $options
     * @return array<int, string>
     */
    private function fourOptionSlots(array $options): array
    {
        return array_map(
            static fn (?string $option): string => $option ?? '',
            array_pad(array_values($options), 4, ''),
        );
    }

    public function deleteQuestion(Quiz $quiz, int $questionId): void
    {
        $question = $quiz->questions()->findOrFail($questionId);
        $question->delete();
    }
}
