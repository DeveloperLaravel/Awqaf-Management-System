<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class StudentAssessment extends Model
{
    protected $table = 'student_assessments';

    public const LEVEL_EXCELLENT = 'excellent';
    public const LEVEL_VERY_GOOD = 'very_good';
    public const LEVEL_GOOD = 'good';
    public const LEVEL_ACCEPTABLE = 'acceptable';
    public const LEVEL_NEEDS_FOLLOW_UP = 'needs_follow_up';

    public const LEVELS = [
        self::LEVEL_EXCELLENT => 'ممتاز',
        self::LEVEL_VERY_GOOD => 'جيد جداً',
        self::LEVEL_GOOD => 'جيد',
        self::LEVEL_ACCEPTABLE => 'مقبول',
        self::LEVEL_NEEDS_FOLLOW_UP => 'يحتاج متابعة',
    ];

    /**
     * أوزان التقييم الأسبوعي.
     */
    public const WEIGHTS = [
        'memorization_score' => 0.25,
        'review_score' => 0.25,
        'tajweed_score' => 0.20,
        'attendance_score' => 0.15,
        'discipline_score' => 0.15,
    ];

    protected $fillable = [
        'center_id',
        'student_id',
        'circle_id',
        'assessed_by',
        'week_start',
        'week_end',
        'memorization_score',
        'review_score',
        'tajweed_score',
        'attendance_score',
        'discipline_score',
        'total_score',
        'level',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',

            'memorization_score' => 'decimal:2',
            'review_score' => 'decimal:2',
            'tajweed_score' => 'decimal:2',
            'attendance_score' => 'decimal:2',
            'discipline_score' => 'decimal:2',
            'total_score' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (StudentAssessment $assessment): void {
            $scores = [];

            foreach (self::WEIGHTS as $field => $weight) {
                $value = $assessment->{$field};

                if ($value === null) {
                    throw new \InvalidArgumentException(
                        "درجة {$field} مطلوبة قبل اعتماد التقييم."
                    );
                }

                if (! is_numeric($value) || $value < 0 || $value > 100) {
                    throw new \InvalidArgumentException(
                        "قيمة {$field} يجب أن تكون بين 0 و100."
                    );
                }

                $scores[] = (float) $value * $weight;
            }

            if (
                $assessment->week_start &&
                $assessment->week_end &&
                $assessment->week_end->lt($assessment->week_start)
            ) {
                throw new \InvalidArgumentException(
                    'تاريخ نهاية الأسبوع يجب ألا يسبق تاريخ بدايته.'
                );
            }

            $assessment->total_score = round(array_sum($scores), 2);
            $assessment->level = self::levelFromScore(
                (float) $assessment->total_score
            );
        });
    }

    public static function levelFromScore(float $score): string
    {
        return match (true) {
            $score >= 90 => self::LEVEL_EXCELLENT,
            $score >= 80 => self::LEVEL_VERY_GOOD,
            $score >= 70 => self::LEVEL_GOOD,
            $score >= 60 => self::LEVEL_ACCEPTABLE,
            default => self::LEVEL_NEEDS_FOLLOW_UP,
        };
    }

    public function getLevelLabelAttribute(): string
    {
        return self::LEVELS[$this->level] ?? 'غير محدد';
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class);
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    /**
     * نطاق تقييمات مركز معين.
     */
    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    /**
     * تقييمات طالب معين.
     */
    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * تقييمات أسبوع معين.
     */
    public function scopeForWeek(
        Builder $query,
        string $weekStart
    ): Builder {
        return $query->whereDate('week_start', $weekStart);
    }
}
