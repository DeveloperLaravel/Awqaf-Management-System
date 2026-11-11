<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\AttendanceRecord;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Student extends Model
{
     protected $fillable = [
        'center_id',
        'guardian_id',
        'student_code',
        'full_name',
        'birth_date',
        'enrollment_date',
        'gender',
        'school_grade',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'enrollment_date' => 'date',
            'is_active' => 'boolean',
        ];
    }
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

public function guardians(): BelongsToMany
{
    return $this->belongsToMany(
        Guardian::class,
        'guardian_student',
        'student_id',
        'guardian_id',
    )->withPivot([
            'id',
            'center_id',
            'relationship',
            'is_primary',
            'can_receive_notifications',
            'can_pickup',
    ]);
}

public function circles(): BelongsToMany
{
    return $this->belongsToMany(
        Circle::class,
        'circle_student',
        'student_id',
        'circle_id',
       
    )
        ->withPivot(['center_id', 'joined_at', 'left_at'])
        ->withTimestamps();
}


public function activeCircle()
{
    return $this->circles()
        ->wherePivotNull('left_at');
}

public function attendanceRecords(): HasMany
{
    return $this->hasMany(AttendanceRecord::class, 'student_id');
}

}
