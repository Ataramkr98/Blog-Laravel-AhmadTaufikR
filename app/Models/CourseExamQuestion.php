<?php

namespace App\Models;

use Database\Factories\CourseExamQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $correct_option_index
 * @property array<int, string> $options
 */
class CourseExamQuestion extends Model
{
    /** @use HasFactory<CourseExamQuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'question',
        'options',
        'correct_option_index',
        'explanation',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_option_index' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
