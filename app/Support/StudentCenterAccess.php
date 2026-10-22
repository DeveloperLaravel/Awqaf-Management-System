<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Center;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StudentCenterAccess
{
    /**
     * إرجاع معرفات الفروع الفعالة والمعتمدة المتاحة للمستخدم.
     *
     * للمشرف العام: جميع الفروع.
     * لمدير المركز: الفروع المرتبطة عبر center_user
     * بالإضافة إلى مركز users.center_id إن كان صالحًا.
     */
    public static function branchIdsFor(User $user): array
    {
        $query = Center::query()
            ->whereNotNull('parent_center_id')
            ->where('is_active', true)
            ->where('approval_status', 'active');

        if ($user->hasRole('super_admin')) {
            return $query->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if (! $user->hasRole('center_manager')) {
            return [];
        }

        $ids = DB::table('center_user')
            ->where('user_id', $user->id)
            ->pluck('center_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($user->center_id !== null) {
            $ids[] = (int) $user->center_id;
        }

        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return [];
        }

        return $query
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * هل يملك المستخدم حق إدارة هذا الفرع؟
     */
    public static function canAccessBranch(
        User $user,
        int|string|null $centerId
    ): bool {
        if ($centerId === null) {
            return false;
        }

        return in_array(
            (int) $centerId,
            self::branchIdsFor($user),
            true
        );
    }
}
