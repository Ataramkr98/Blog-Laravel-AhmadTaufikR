<?php

namespace App\Models;

use App\Support\PublicMediaUrl;
use Carbon\CarbonInterface;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string $level
 * @property string $status
 * @property int $passing_score
 * @property CarbonInterface|null $published_at
 */
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    public function getFeaturedImageAttribute(?string $value): ?string
    {
        return PublicMediaUrl::resolve($value);
    }

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'level',
        'featured_image',
        'status',
        'passing_score',
        'published_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'passing_score' => 'integer',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Course $course): void {
            if (blank($course->slug)) {
                $course->slug = Str::slug($course->title);
            }

            if ($course->status === 'published' && $course->published_at === null) {
                $course->published_at = now();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<CourseModule, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('sort_order');
    }

    /**
     * @return HasManyThrough<CourseLesson, CourseModule, $this>
     */
    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(CourseLesson::class, CourseModule::class, 'course_id', 'course_module_id')
            ->orderBy('course_modules.sort_order')
            ->orderBy('course_lessons.sort_order');
    }

    /**
     * @return HasMany<CourseExamQuestion, $this>
     */
    public function examQuestions(): HasMany
    {
        return $this->hasMany(CourseExamQuestion::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<CourseEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    /**
     * @return HasMany<FinalExamAttempt, $this>
     */
    public function finalExamAttempts(): HasMany
    {
        return $this->hasMany(FinalExamAttempt::class);
    }

    /**
     * @return HasMany<Certificate, $this>
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function enrollmentFor(?User $user): ?CourseEnrollment
    {
        if (! $user) {
            return null;
        }

        return CourseEnrollment::query()
            ->where('course_id', $this->id)
            ->where('user_id', $user->id)
            ->first();
    }

    public function certificateFor(User $user): ?Certificate
    {
        return Certificate::query()
            ->where('course_id', $this->id)
            ->where('user_id', $user->id)
            ->first();
    }

    public function requiredLessonsCount(): int
    {
        return $this->lessons()->where('is_required', true)->count();
    }

    public function completedLessonsCountFor(User $user): int
    {
        return LessonProgress::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->whereHas('lesson', function (Builder $query): void {
                $query->where('is_required', true)
                    ->whereHas('module', fn (Builder $moduleQuery) => $moduleQuery->where('course_id', $this->id));
            })
            ->count();
    }

    public function progressPercentFor(User $user): int
    {
        $requiredLessons = $this->requiredLessonsCount();

        if ($requiredLessons === 0) {
            return 0;
        }

        return (int) round(($this->completedLessonsCountFor($user) / $requiredLessons) * 100);
    }

    public function hasCompletedRequiredLessons(User $user): bool
    {
        $requiredLessons = $this->requiredLessonsCount();

        if ($requiredLessons === 0) {
            return true;
        }

        return $this->completedLessonsCountFor($user) >= $requiredLessons;
    }
}
