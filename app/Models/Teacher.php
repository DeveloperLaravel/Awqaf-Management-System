<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Center;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Teacher extends Model
{
    protected $fillable = [
        'center_id',
        'user_id',
        'full_name',
        'phone',
        'email',
        'qualification',
        'hire_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
     /**
     * جميع المراكز المرتبط بها المعلم.
     */
    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(
            Center::class,
            'center_teacher',
            'teacher_id',
            'center_id',
        )
            ->withPivot('is_active', 'joined_at')
            ->withTimestamps();
    }
    
}
