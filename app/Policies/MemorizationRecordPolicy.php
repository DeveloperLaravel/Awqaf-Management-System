<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MemorizationRecord;
use Illuminate\Auth\Access\HandlesAuthorization;

class MemorizationRecordPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MemorizationRecord');
    }

    public function view(AuthUser $authUser, MemorizationRecord $memorizationRecord): bool
    {
        return $authUser->can('View:MemorizationRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MemorizationRecord');
    }

    public function update(AuthUser $authUser, MemorizationRecord $memorizationRecord): bool
    {
        return $authUser->can('Update:MemorizationRecord');
    }

    public function delete(AuthUser $authUser, MemorizationRecord $memorizationRecord): bool
    {
        return $authUser->can('Delete:MemorizationRecord');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MemorizationRecord');
    }

    public function restore(AuthUser $authUser, MemorizationRecord $memorizationRecord): bool
    {
        return $authUser->can('Restore:MemorizationRecord');
    }

    public function forceDelete(AuthUser $authUser, MemorizationRecord $memorizationRecord): bool
    {
        return $authUser->can('ForceDelete:MemorizationRecord');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MemorizationRecord');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MemorizationRecord');
    }

    public function replicate(AuthUser $authUser, MemorizationRecord $memorizationRecord): bool
    {
        return $authUser->can('Replicate:MemorizationRecord');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MemorizationRecord');
    }

}