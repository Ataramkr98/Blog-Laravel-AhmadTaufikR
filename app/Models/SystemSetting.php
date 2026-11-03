<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Single-row platform settings table.
 *
 * @property bool $maintenance_mode
 * @property bool $review_notifications
 * @property int $glossary_cache_minutes
 */
class SystemSetting extends Model
{
    /**
     * The values that are persisted in the single settings row.
     * Used to hydrate a lightweight in-memory snapshot from cache.
     */
    private const CACHE_KEY = 'system_settings_values';

    protected $fillable = [
        'maintenance_mode',
        'review_notifications',
        'glossary_cache_minutes',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_mode' => 'boolean',
            'review_notifications' => 'boolean',
            'glossary_cache_minutes' => 'integer',
        ];
    }

    /**
     * Always one row. We cache the primitive values (not the model instance)
     * so the snapshot survives across tests / migrations without going stale.
     */
    public static function current(): self
    {
        $values = Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function (): array {
            $row = self::query()->firstOr(fn () => self::query()->create([
                'maintenance_mode' => false,
                'review_notifications' => true,
                'glossary_cache_minutes' => 60,
            ]));

            return [
                'id' => (int) $row->id,
                'maintenance_mode' => (bool) $row->maintenance_mode,
                'review_notifications' => (bool) $row->review_notifications,
                'glossary_cache_minutes' => (int) $row->glossary_cache_minutes,
            ];
        });

        $instance = new self;
        $instance->setRawAttributes($values, sync: true);
        $instance->exists = true;

        return $instance;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function (): void {
            Cache::forget(self::CACHE_KEY);
        });
    }
}
