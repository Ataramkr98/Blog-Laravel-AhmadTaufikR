<?php

namespace App\Models;

use App\Support\PublicMediaUrl;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @property-read Category|null $category
 * @property-read User|null $author
 * @property-read Collection<int, Tag> $tags
 * @property-read Collection<int, Citation> $citations
 * @property-read Collection<int, QuizQuestion> $quizQuestions
 * @property-read Collection<int, Glossary> $glossaries
 * @property-read Collection<int, ContentReview> $reviews
 * @property CarbonInterface|null $published_at
 */
class Article extends Model
{
    use HasFactory;

    public function getFeaturedImageAttribute(?string $value): ?string
    {
        return PublicMediaUrl::resolve($value);
    }

    protected $fillable = [
        'user_id',
        'category_id',
        'level',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'published_at',
        'view_count',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (Article $article): void {
            if ($article->courseLessons()->exists()) {
                throw ValidationException::withMessages([
                    'article' => 'Artikel masih digunakan dalam kursus. Arsipkan artikel agar progres siswa tetap tersimpan.',
                ]);
            }
        });

        static::saving(function (Article $article): void {
            if (blank($article->slug)) {
                $article->slug = Str::slug($article->title);
            }

            if ($article->category_id && $article->category) {
                $article->level = $article->category->level;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function courseLessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function citations(): HasMany
    {
        return $this->hasMany(Citation::class)->orderBy('sort_order');
    }

    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort_order');
    }

    public function glossaries(): BelongsToMany
    {
        return $this->belongsToMany(Glossary::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(ContentReview::class, 'reviewable')->latest();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
