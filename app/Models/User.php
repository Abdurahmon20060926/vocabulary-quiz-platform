<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'google_id',
        'name',
        'email',
        'avatar_url',
    ];

    protected $hidden = [
        'email',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(QuizVote::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(QuizRun::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(QuizUserStat::class);
    }

    public function hasVotedFor(Quiz $quiz): bool
    {
        return $this->votes()->where('quiz_id', $quiz->id)->exists();
    }
}
