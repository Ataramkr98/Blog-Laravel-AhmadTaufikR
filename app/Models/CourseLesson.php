<?php

namespace App\Models;

use Database\Factories\CourseLessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|null $title
 * @property-read Article $article
 * @property-read CourseModule $module
 */
class CourseLesson extends Model
{
    /** @use HasFactory<CourseLessonFactory> */
    use HasFactory;

    protected $fillable = [
        'course_module_id',
        'article_id',
        'title',
        'sort_order',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CourseModule, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * @return HasMany<LessonProgress, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function displayTitle(): string
    {
        return $this->title ?: $this->article->title;
    }
}
