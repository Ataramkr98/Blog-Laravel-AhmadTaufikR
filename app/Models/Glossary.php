<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Glossary extends Model
{
    use HasFactory;

    protected $fillable = [
        'term',
        'common_name',
        'definition',
        'level',
        'category',
    ];

    protected static function booted(): void
    {
        $invalidate = fn () => Cache::forever('glossary_revision', (string) Str::uuid());
        static::saved($invalidate);
        static::deleted($invalidate);
    }

    public function scopeLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', $level);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search): void {
            $query->whereLike('term', "%{$search}%")
                ->orWhereLike('common_name', "%{$search}%")
                ->orWhereLike('definition', "%{$search}%");
        });
    }
}
