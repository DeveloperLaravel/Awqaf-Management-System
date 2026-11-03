<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Guardian extends Model
{
    use HasFactory ;

    protected $fillable = [
        'center_id',
        'full_name',
        'phone',
        'email',
        'national_id',
        'occupation',
        'address',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * المركز التابع له ولي الأمر.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * الطلبة المرتبطون بولي الأمر.
     */

public function students(): BelongsToMany
{
    return $this->belongsToMany(
        Student::class,
        'guardian_student',
        'guardian_id',
        'student_id',
    )->withPivot([
        'center_id',
        'relationship',
    ]);
}
}