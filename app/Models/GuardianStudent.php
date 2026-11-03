<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianStudent extends Model
{
    protected $table = 'guardian_student';

    protected $fillable = [
        'center_id',
        'guardian_id',
        'student_id',
        'relationship',
        'is_primary',
        'can_receive_notifications',
        'can_pickup',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'can_receive_notifications' => 'boolean',
        'can_pickup' => 'boolean',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}