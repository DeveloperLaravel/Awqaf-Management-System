<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Center;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureCenterIsApproved
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        // السماح للطلبات غير المسجلة بمتابعة مسار المصادقة.
        if (! $user) {
            return $next($request);
        }

        // مدير النظام لا يحتاج إلى مركز مرتبط بحسابه.
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // المراكز المرتبطة مباشرة بالمستخدم.
        $centerIds = $user->managedCenters()
            ->pluck('centers.id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        // دعم الحسابات القديمة.
        if ($user->center_id !== null) {
            $centerIds[] = (int) $user->center_id;
        }

        $centerIds = array_values(array_unique($centerIds));

        if ($centerIds === []) {
            abort(
                403,
                'هذا الحساب غير مرتبط بأي مركز. يرجى مراجعة إدارة النظام.'
            );
        }

        // يجب وجود مركز واحد على الأقل معتمد ومفعّل.
        $approvedCenter = Center::query()
            ->whereIn('id', $centerIds)
            ->where('is_active', true)
            ->where('approval_status', 'active')
            ->first();

        Log::info('Center approval middleware diagnostic', [
            'user_id' => $user->id,
            'roles' => $user->getRoleNames()->toArray(),
            'legacy_center_id' => $user->center_id,
            'managed_center_ids' => $centerIds,
            'approved_center_id' => $approvedCenter?->id,
            'path' => $request->path(),
        ]);

        if (! $approvedCenter) {
            abort(
                403,
                'لا يوجد حسابك مرتبطًا بمركز معتمد ومفعّل. يرجى مراجعة إدارة النظام.'
            );
        }

        return $next($request);
    }
}
