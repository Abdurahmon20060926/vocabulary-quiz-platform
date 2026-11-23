<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class QuizRun extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id', 'quiz_id', 'user_id', 'guest_token', 'mode',
        'question_order', 'option_orders', 'salt',
        'current_index', 'current_issued_at', 'status',
        'failure_reason', 'reached', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'question_order' => 'array',
        'option_orders' => 'array',
        'current_index' => 'integer',
        'current_issued_at' => 'datetime:Y-m-d H:i:s.u',
        'reached' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (QuizRun $run) {
            if (empty($run->id)) {
                $run->id = strtolower(Str::ulid());
            }
            if (empty($run->started_at)) {
                $run->started_at = now();
            }
        });
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(RunAnswer::class, 'quiz_run_id');
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function currentQuestion(): ?Question
    {
        $ids = $this->question_order;
        if (!isset($ids[$this->current_index])) {
            return null;
        }
        return Question::find($ids[$this->current_index]);
    }

    public function totalQuestions(): int
    {
        return count($this->question_order ?? []);
    }
}
