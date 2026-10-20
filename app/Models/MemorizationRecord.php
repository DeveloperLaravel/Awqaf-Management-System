<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class MemorizationRecord extends Model
{
     protected $fillable = [
        'center_id',
        'student_id',
        'circle_id',
        'teacher_id',
        'record_date',
        'record_type',
        'surah_name',
        'from_ayah',
        'to_ayah',
        'grade',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'from_ayah' => 'integer',
            'to_ayah' => 'integer',
            'grade' => 'decimal:2',
        ];
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

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
