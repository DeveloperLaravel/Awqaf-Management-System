<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Center;
use App\Models\User;
use Illuminate\Support\Collection;

class CenterHierarchy
{
    /**
     * إرجاع المركز المحدد وجميع المراكز التابعة له،
     * بما فيها المراكز الفرعية متعددة المستويات.
     *
     * لا نستبعد المراكز المعلقة هنا؛ لأن المدير يحتاج إلى
     * رؤيتها حتى يتمكن من مراجعة طلبات الاعتماد.
     */
    public function accessibleIds(int $centerId): array
    {
        $allIds = [];
        $pendingIds = [$centerId];

        while ($pendingIds !== []) {
            $currentBatch = array_values(array_unique(
                array_map('intval', $pendingIds)
            ));

            $pendingIds = [];

            foreach ($currentBatch as $id) {
                if (in_array($id, $allIds, true)) {
                    continue;
                }

                $allIds[] = $id;
            }

            if ($currentBatch === []) {
                continue;
            }

            $children = Center::query()
                ->whereIn('parent_center_id', $currentBatch)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $pendingIds = array_values(array_diff(
                $children,
                $allIds,
            ));
        }

        return $allIds;
    }

    /**
     * إرجاع المراكز التي يستطيع المستخدم الوصول إليها
     * انطلاقًا من مراكزه المرتبطة مباشرة.
     */
    public function accessibleIdsForUser(User $user): array
    {
        if ($user->hasRole('super_admin')) {
            return Center::query()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        $assignedIds = $user->managedCenters()
            ->pluck('centers.id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        // دعم الحسابات القديمة التي تستخدم users.center_id.
        if ($user->center_id !== null) {
            $assignedIds[] = (int) $user->center_id;
        }

        $assignedIds = array_values(array_unique($assignedIds));
        $accessibleIds = [];

        foreach ($assignedIds as $centerId) {
            $accessibleIds = array_merge(
                $accessibleIds,
                $this->accessibleIds($centerId),
            );
        }

        return array_values(array_unique($accessibleIds));
    }

    /**
     * التحقق من أن المركز ضمن شجرة مركز محدد.
     */
    public function canAccess(int $rootCenterId, int $targetCenterId): bool
    {
        return in_array(
            $targetCenterId,
            $this->accessibleIds($rootCenterId),
            true,
        );
    }

    /**
     * التحقق من أن المستخدم يستطيع الوصول إلى مركز معين.
     */
    public function canUserAccess(User $user, int $targetCenterId): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return in_array(
            $targetCenterId,
            $this->accessibleIdsForUser($user),
            true,
        );
    }

    /**
     * المراكز التي يديرها المستخدم مباشرة فقط.
     * تُستخدم عند اختيار مركز أب لإنشاء مركز فرعي.
     */
    public function directlyManagedCenters(User $user): Collection
    {
        if ($user->hasRole('super_admin')) {
            return Center::query()
                ->orderBy('name')
                ->get();
        }

        $ids = $user->managedCenters()
            ->pluck('centers.id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($user->center_id !== null) {
            $ids[] = (int) $user->center_id;
        }

        return Center::query()
            ->whereIn('id', array_values(array_unique($ids)))
            ->orderBy('name')
            ->get();
    }
}