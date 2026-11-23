<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id', 'position', 'prompt', 'options', 'correct_option_index',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_option_index' => 'integer',
        'position' => 'integer',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function correctOptionText(): string
    {
        return $this->options[$this->correct_option_index] ?? '';
    }
}
