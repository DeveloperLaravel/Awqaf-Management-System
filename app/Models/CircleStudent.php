<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CircleStudent extends Model
{
    protected $table = 'circle_student';

    protected $fillable = [
        'center_id',
        'circle_id',
        'student_id',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'joined_at' => 'date',
        'left_at' => 'date',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}