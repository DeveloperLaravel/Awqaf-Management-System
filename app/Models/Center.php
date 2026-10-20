<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;
class Center extends Model
{
protected $fillable = [
    'name',
    'region_id',
    'code',
    'phone',
    'email',
    'address',
    'is_active',
    'parent_center_id',
    'approval_status',
    'parent_approved_by',
    'parent_approved_at',
    'approved_by',
    'approved_at',
    'rejection_reason',
];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }


    /**
     * المستخدمون التابعون للمركز.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
        public function region(): BelongsTo
{
    return $this->belongsTo(Region::class);
}
/* المركز الأب.
 */
public function parentCenter(): BelongsTo
{
    return $this->belongsTo(
        Center::class,
        'parent_center_id'
    );
}
/**
 * المراكز الفرعية المباشرة.
 */
public function childCenters(): HasMany
{
    return $this->hasMany(
        Center::class,
        'parent_center_id'
    );
}
/**
 * المستخدم الذي وافق مبدئيًا.
 */
public function parentApprover(): BelongsTo
{
    return $this->belongsTo(
        User::class,
        'parent_approved_by'
    );
}
/**
 * المستخدم الذي اعتمد المركز نهائيًا.
 */
public function finalApprover(): BelongsTo
{
    return $this->belongsTo(
        User::class,
        'approved_by'
    );
}
                                    
    /**
     * الطلبة التابعون للمركز.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * أولياء الأمور التابعون للمركز.
     */
    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    /**
     * المدرسون التابعون للمركز.
     */
    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    /**
     * حلقات التحفيظ التابعة للمركز.
     */
    public function circles(): HasMany
    {
        return $this->hasMany(Circle::class);
    }

public function managedByUsers(): BelongsToMany
{
    return $this->belongsToMany(
        User::class,
        'center_user',
        'center_id',
        'user_id',
    )->withTimestamps();
}
// public function managers(): BelongsToMany
// {
//     return $this->belongsToMany(User::class, 'center_user')
//         ->withTimestamps();
// }
}