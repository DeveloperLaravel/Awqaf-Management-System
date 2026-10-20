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

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)
            ->withPivot('joined_at', 'left_at')
            ->withTimestamps();
    }
}
