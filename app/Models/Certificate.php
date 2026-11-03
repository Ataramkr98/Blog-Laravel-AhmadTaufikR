<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property string $certificate_number
 * @property CarbonInterface|null $issued_at
 * @property CarbonInterface|null $revoked_at
 */
class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'user_id',
        'final_exam_attempt_id',
        'certificate_number',
        'issued_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Certificate $certificate): void {
            if (blank($certificate->certificate_number)) {
                $certificate->certificate_number = static::generateNumber();
            }

            if ($certificate->issued_at === null) {
                $certificate->issued_at = now();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'certificate_number';
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(FinalExamAttempt::class, 'final_exam_attempt_id');
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'BIO-'.now()->format('Y').'-'.Str::upper(Str::random(8));
        } while (static::query()->where('certificate_number', $number)->exists());

        return $number;
    }
}
