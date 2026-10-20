<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
class CenterScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // خارج جلسة المستخدم لا نفرض نطاقًا تلقائيًا.
        // يجب حماية المسارات والعمليات الأخرى بصلاحياتها.
        if (! $user) {
            return;
        }

        // المدير العام يمكنه الاطلاع على جميع المراكز.
     if (Gate::allows('centers.view_all')) {
    return;
}

        // المستخدم الذي لا يتبع مركزًا لا يرى أي سجل.
        if (! $user->center_id) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where(
            $model->qualifyColumn('center_id'),
            $user->center_id
        );
    }
}
