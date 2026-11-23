<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunAnswer extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'quiz_run_id', 'question_id', 'chosen_option_index',
        'is_correct', 'failure_reason', 'time_ms', 'created_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'chosen_option_index' => 'integer',
        'time_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(QuizRun::class, 'quiz_run_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
