<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StudentAssessment;
use App\Models\User;
use App\Services\CenterHierarchy;

class StudentAssessmentPolicy
{
    private function canAccessCenter(User $user, int $centerId): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $ids = app(CenterHierarchy::class)
            ->accessibleIdsForUser($user);

        return in_array($centerId, $ids, true);
    }

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:StudentAssessment');
    }

    public function view(User $user, StudentAssessment $record): bool
    {
        return $user->can('View:StudentAssessment')
            && $this->canAccessCenter($user, (int) $record->center_id);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:StudentAssessment');
    }

    public function update(User $user, StudentAssessment $record): bool
    {
        return $user->can('Update:StudentAssessment')
            && $this->canAccessCenter($user, (int) $record->center_id);
    }

    public function delete(User $user, StudentAssessment $record): bool
    {
        return $user->can('Delete:StudentAssessment')
            && $this->canAccessCenter($user, (int) $record->center_id);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('Delete:StudentAssessment');
    }
}
