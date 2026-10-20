<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ): Response {
        $user = $request->user();

        if (! $user) {
            abort(403, 'يجب تسجيل الدخول أولاً.');
        }

        // المدير العام لديه جميع الصلاحيات
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        if (! $user->can($permission)) {
            abort(403, 'ليس لديك صلاحية للوصول إلى هذه الصفحة.');
        }

        return $next($request);
    }
}
