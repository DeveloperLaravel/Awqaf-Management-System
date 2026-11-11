<?php

declare(strict_types=1);

namespace App\Concerns;
use App\Services\CenterHierarchy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasDashboardCenterScope
{
    /**
     * المراكز التي يستطيع المستخدم الوصول إليها.
     */
    protected function dashboardCenterIds(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        return array_values(array_unique(array_map(
            'intval',
            app(CenterHierarchy::class)->accessibleIdsForUser($user)
        )));
    }

    /**
     * تطبيق نطاق المراكز على أي موديل يحتوي center_id.
     */
    protected function dashboardQuery(string $model): Builder
    {
        return $model::query()
            ->whereIn('center_id', $this->dashboardCenterIds());
    }
}