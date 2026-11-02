<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Circle extends Model
{
    protected $fillable = [
        'center_id',
        'teacher_id',
        'name',
        'code',
        'level',
        'room',
        'capacity',
        'starts_at',
        'ends_at',
        'days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
             'days' => 'array',
        ];
        
    }


       /**
     * المركز التابع له الحلقة.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
      /**
     * المعلم المسؤول عن الحلقة.
     */

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

      /**
     * الطلاب المسجلون في الحلقة.
     */

     public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            Student::class,
            'circle_student',
            'circle_id',
            'student_id',
        )
    //      return $this->belongsToMany(
    //     Student::class,
    //     'circle_student'
    // )
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();
    }
}
