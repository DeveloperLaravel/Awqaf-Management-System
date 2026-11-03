<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Center extends Model
{
    /**
     * الحقول المسموح بتعبئتها من النماذج العادية.
     *
     * حقول الهرمية والاعتماد تُعدّل من إجراءات الخادم
     * باستخدام forceFill بعد التحقق من الصلاحيات.
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

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'parent_approved_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    // المنطقة
    // -----------------------------------------------------------------

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    // -----------------------------------------------------------------
    // الهيكل الهرمي
    // -----------------------------------------------------------------

    /**
     * المركز الأب المباشر.
     */
    public function parentCenter(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'parent_center_id');
    }

    /**
     * الفروع المباشرة التابعة لهذا المركز.
     */
    public function childCenters(): HasMany
    {
        return $this->hasMany(Center::class, 'parent_center_id');
    }

    /**
     * جلب جميع الفروع التابعة، المباشرة وغير المباشرة.
     *
     * هذه دالة استعلام وليست علاقة Eloquent؛
     * لأنها ترجع مجموعة من جميع المستويات.
     */
    public function descendants(): Collection
    {
        $parentIds = [$this->getKey()];
        $descendantIds = [];

        while (! empty($parentIds)) {
            $children = static::query()
                ->whereIn('parent_center_id', $parentIds)
                ->whereNotIn('id', $descendantIds)
                ->get(['id']);

            $newIds = $children->pluck('id')->all();

            if (empty($newIds)) {
                break;
            }

            $descendantIds = array_merge($descendantIds, $newIds);
            $parentIds = $newIds;
        }

        if (empty($descendantIds)) {
            return new Collection();
        }

        return static::query()
            ->whereIn('id', $descendantIds)
            ->with(['region', 'parentCenter'])
            ->orderBy('id')
            ->get();
    }

    // -----------------------------------------------------------------
    // الاعتماد والموافقات
    // -----------------------------------------------------------------

    public function parentApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_approved_by');
    }

    public function finalApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // -----------------------------------------------------------------
    // المستخدمون
    // -----------------------------------------------------------------

    /**
     * المستخدمون المرتبطون بالمركز عبر users.center_id.
     *
     * هذه العلاقة لا تعني بالضرورة أنهم مديرون.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'center_id');
    }

    /**
     * المستخدمون المكلفون بإدارة المركز عبر center_user.
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

    // -----------------------------------------------------------------
    // الطلاب وأولياء الأمور
    // -----------------------------------------------------------------

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'center_id');
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class, 'center_id');
    }

    // -----------------------------------------------------------------
    // المعلمون
    // -----------------------------------------------------------------

    /**
     * المعلم قد يعمل في أكثر من مركز.
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

    // -----------------------------------------------------------------
    // الحلقات
    // -----------------------------------------------------------------

    public function circles(): HasMany
    {
        return $this->hasMany(Circle::class, 'center_id');
    }
}