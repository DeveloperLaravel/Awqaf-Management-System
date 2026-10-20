<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

trait BelongsToCenter
{
    /**
     * عزل السجلات حسب مركز المستخدم.
     */
    protected static function applyCenterScope(
        Builder $query
    ): Builder {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // صلاحية الوصول الشامل للمراكز
        if (Gate::allows('centers.view_all')) {
            return $query;
        }

        if (! $user->center_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            $query->getModel()->qualifyColumn('center_id'),
            $user->center_id
        );
    }

    /**
     * التحقق من أن السجل يتبع مركز المستخدم.
     */
    protected static function belongsToAllowedCenter(
        Model $record
    ): bool {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if (Gate::allows('centers.view_all')) {
            return true;
        }

        return $user->center_id !== null
            && (int) $record->center_id === (int) $user->center_id;
    }
}