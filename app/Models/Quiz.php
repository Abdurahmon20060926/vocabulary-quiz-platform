<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid', 'user_id', 'title', 'description', 'mode',
        'seconds_per_question', 'status', 'is_listed',
        'published_at', 'upvotes_count', 'runs_count',
        'passes_count', 'reached_sum',
    ];

    protected $casts = [
        'is_listed' => 'boolean',
        'published_at' => 'datetime',
        'upvotes_count' => 'integer',
        'runs_count' => 'integer',
        'passes_count' => 'integer',
        'reached_sum' => 'integer',
        'seconds_per_question' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Quiz $quiz) {
            if (empty($quiz->ulid)) {
                $quiz->ulid = strtolower(Str::ulid());
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(QuizVote::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(QuizRun::class);
    }

    public function userStats(): HasMany
    {
        return $this->hasMany(QuizUserStat::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isUnpublished(): bool
    {
        return $this->status === 'unpublished';
    }

    public function passRate(): ?float
    {
        if ($this->runs_count < 10) {
            return null;
        }
        return $this->runs_count > 0 ? $this->passes_count / $this->runs_count : 0;
    }

    public function avgReached(): ?float
    {
        if ($this->runs_count < 10) {
            return null;
        }
        return $this->runs_count > 0 ? $this->reached_sum / $this->runs_count : 0;
    }

    public function questionCount(): int
    {
        return $this->questions()->count();
    }
}
