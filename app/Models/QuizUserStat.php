<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizUserStat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'quiz_id', 'attempts', 'best_reached',
        'passed', 'first_passed_at', 'last_played_at',
    ];

    protected $casts = [
        'passed' => 'boolean',
        'first_passed_at' => 'datetime',
        'last_played_at' => 'datetime',
        'attempts' => 'integer',
        'best_reached' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
