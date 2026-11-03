<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\FinalExamAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonInterface|null $attempted_at
 * @property array<int, int> $answers
 * @property array<int, array<string, mixed>>|null $question_snapshot
 */
class FinalExamAttempt extends Model
{
    /** @use HasFactory<FinalExamAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'user_id',
        'answers',
        'submission_token',
        'question_snapshot',
        'score',
        'total_questions',
        'percentage',
        'passed',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'question_snapshot' => 'array',
            'score' => 'integer',
            'total_questions' => 'integer',
            'percentage' => 'integer',
            'passed' => 'boolean',
            'attempted_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
