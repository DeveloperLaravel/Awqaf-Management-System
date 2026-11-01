<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Center extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    |
    | الحقول الأساسية التي يمكن تعبئتها مباشرة.
    | حقول الاعتماد والموافقة تُدار من خلال إجراءات الخادم
    | وليس من نموذج إنشاء المركز العادي.
    |
    */

    protected $fillable = [
        'name',
        'region_id',
        'code',
        'phone',
        'email',
        'address',
        'is_active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'parent_approved_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Region
    |--------------------------------------------------------------------------
    */

    /**
     * المنطقة التي يتبع لها المركز.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Center Hierarchy
    |--------------------------------------------------------------------------
    */

    /**
     * المركز الأب.
     */
    public function parentCenter(): BelongsTo
    {
        return $this->belongsTo(
            Center::class,
            'parent_center_id',
        );
    }

    /**
     * الفروع المباشرة التابعة للمركز.
     */
    public function childCenters(): HasMany
    {
        return $this->hasMany(
            Center::class,
            'parent_center_id',
        );
    }

    /**
     * جميع الفروع المباشرة وغير المباشرة.
     */
    public function descendants(): HasMany
    {
        return $this->childCenters()->with('descendants');
    }

    /*
    |--------------------------------------------------------------------------
    | Approval Workflow
    |--------------------------------------------------------------------------
    */

    /**
     * المستخدم الذي وافق على الفرع من جهة المركز الأب.
     */
    public function parentApprover(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'parent_approved_by',
        );
    }

    /**
     * المستخدم الذي اعتمد المركز نهائياً.
     */
    public function finalApprover(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    /**
     * جميع المستخدمين التابعين للمركز الأساسي.
     *
     * يعتمد على users.center_id.
     */
    public function users(): HasMany
    {
        return $this->hasMany(
            User::class,
            'center_id',
        );
    }

    /**
     * المستخدمون المكلفون بإدارة هذا المركز.
     *
     * يعتمد على جدول center_user.
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'center_user',
            'center_id',
            'user_id',
        )->withTimestamps();
    }

    /**
     * اسم بديل للتوافق مع الكود القديم.
     */
    public function managedByUsers(): BelongsToMany
    {
        return $this->managers();
    }

    /*
    |--------------------------------------------------------------------------
    | Students
    |--------------------------------------------------------------------------
    */

    /**
     * الطلاب التابعون للمركز.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Guardians
    |--------------------------------------------------------------------------
    */

    /**
     * أولياء الأمور التابعون للمركز.
     */
    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Teachers
    |--------------------------------------------------------------------------
    */

    /**
     * المعلمون المرتبطون بالمركز.
     *
     * يمكن للمعلم العمل في أكثر من مركز.
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(
            Teacher::class,
            'center_teacher',
            'center_id',
            'teacher_id',
        )
            ->withPivot([
                'is_active',
                'joined_at',
            ])
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Circles
    |--------------------------------------------------------------------------
    */

    /**
     * حلقات التحفيظ التابعة للمركز.
     */
    public function circles(): HasMany
    {
        return $this->hasMany(Circle::class);
    }
}
