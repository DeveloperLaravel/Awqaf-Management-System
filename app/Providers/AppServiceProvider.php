<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AttendanceRecord;
use App\Models\Center;
use App\Models\Circle;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;

use App\Policies\AttendanceRecordPolicy;
use App\Policies\CirclePolicy;
use App\Policies\GuardianPolicy;
use App\Policies\StudentPolicy;
use App\Policies\TeacherPolicy;
use App\Policies\UserPolicy;

use App\Services\CenterHierarchy;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
         * تسجيل السياسات.
         */
        Gate::policy(
            AttendanceRecord::class,
            AttendanceRecordPolicy::class,
        );

        Gate::policy(
            Guardian::class,
            GuardianPolicy::class,
        );

        Gate::policy(
            Student::class,
            StudentPolicy::class,
        );

        Gate::policy(
            Circle::class,
            CirclePolicy::class,
        );

        Gate::policy(
            Teacher::class,
            TeacherPolicy::class,
        );

        Gate::policy(
            User::class,
            UserPolicy::class,
        );

        /*
         * صلاحيات الحلقات.
         */
        Gate::define(
            'circles.view_own',
            fn (User $user): bool =>
                $user->hasPermissionTo('circles.view_own', 'web'),
        );

        Gate::define(
            'circles.view',
            fn (User $user): bool =>
                $user->hasPermissionTo('circles.view', 'web'),
        );

        Gate::define(
            'circles.create',
            fn (User $user): bool =>
                $user->hasPermissionTo('circles.create', 'web'),
        );

        Gate::define(
            'circles.update',
            fn (User $user): bool =>
                $user->hasPermissionTo('circles.update', 'web'),
        );

        Gate::define(
            'circles.delete',
            fn (User $user): bool =>
                $user->hasPermissionTo('circles.delete', 'web'),
        );

        /*
         * صلاحيات المعلمين.
         */
        Gate::define(
            'teachers.view',
            fn (User $user): bool =>
                $user->hasPermissionTo('teachers.view', 'web'),
        );

        Gate::define(
            'teachers.create',
            fn (User $user): bool =>
                $user->hasPermissionTo('teachers.create', 'web'),
        );

        Gate::define(
            'teachers.update',
            fn (User $user): bool =>
                $user->hasPermissionTo('teachers.update', 'web'),
        );

        Gate::define(
            'teachers.delete',
            fn (User $user): bool =>
                $user->hasPermissionTo('teachers.delete', 'web'),
        );

        Gate::define(
            'teachers.manage_all',
            fn (User $user): bool =>
                $user->hasRole('super_admin')
                && $user->hasPermissionTo('teachers.manage_all', 'web'),
        );

        /*
         * صلاحيات المراكز.
         */
        Gate::define(
            'centers.view',
            fn (User $user): bool =>
                $user->hasPermissionTo('centers.view', 'web'),
        );

        Gate::define(
            'centers.view_all',
            fn (User $user): bool =>
                $user->hasPermissionTo('centers.view_all', 'web'),
        );

        Gate::define(
            'centers.create',
            fn (User $user): bool =>
                $user->hasPermissionTo('centers.create', 'web'),
        );

        Gate::define(
            'centers.create_child',
            fn (User $user): bool =>
                $user->hasPermissionTo('centers.create_child', 'web')
                && app(CenterHierarchy::class)
                    ->directlyManagedCenters($user)
                    ->exists(),
        );

        Gate::define(
            'centers.update',
            fn (User $user): bool =>
                $user->hasPermissionTo('centers.update', 'web'),
        );

        Gate::define(
            'centers.delete',
            fn (User $user): bool =>
                $user->hasPermissionTo('centers.delete', 'web'),
        );

        /*
         * موافقة مدير المركز الأب.
         */
        Gate::define(
            'centers.approve_child',
            fn (User $user): bool =>
                $user->hasPermissionTo('Update:Center', 'web'),
        );

        /*
         * الاعتماد النهائي.
         */
        Gate::define(
            'centers.approve_final',
            fn (User $user): bool =>
                $user->hasRole('super_admin'),
        );

        /*
         * فحص الوصول إلى مركز محدد.
         */
        Gate::define(
            'centers.access',
            function (User $user, Center $center): bool {
                if ($user->hasRole('super_admin')) {
                    return true;
                }

                return app(CenterHierarchy::class)->canUserAccess(
                    $user,
                    (int) $center->getKey(),
                );
            },
        );

        /*
         * العمليات المتقدمة للمراكز.
         */
        Gate::define(
            'centers.delete_any',
            fn (User $user): bool =>
                $user->hasPermissionTo('centers.delete_any', 'web')
                && $user->can('centers.view_all'),
        );

        Gate::define(
            'centers.restore',
            fn (User $user): bool =>
                $user->hasPermissionTo('centers.restore', 'web'),
        );

        Gate::define(
            'centers.force_delete',
            fn (User $user): bool =>
                $user->hasPermissionTo('centers.force_delete', 'web'),
        );

        Gate::define(
            'centers.restore_any',
            fn (User $user): bool =>
                $user->hasPermissionTo('centers.restore_any', 'web')
                && $user->can('centers.view_all'),
        );

        Gate::define(
            'centers.force_delete_any',
            fn (User $user): bool =>
                $user->hasPermissionTo('centers.force_delete_any', 'web')
                && $user->can('centers.view_all'),
        );

        Gate::define(
            'centers.replicate',
            fn (User $user): bool =>
                $user->hasPermissionTo('centers.replicate', 'web'),
        );

        Gate::define(
            'centers.reorder',
            fn (User $user): bool =>
                $user->hasPermissionTo('centers.reorder', 'web')
                && $user->can('centers.view_all'),
        );

        /*
         * توافق مع أسماء صلاحيات المستخدمين القديمة.
         * تُستخدم في الملفات التي تستدعي Gate::allows('users.*').
         */
        Gate::define(
            'users.view_all',
            fn (User $user): bool =>
                $user->hasPermissionTo('users.view_all', 'web'),
        );

        Gate::define(
            'users.view',
            fn (User $user): bool =>
                $user->hasPermissionTo('users.view', 'web'),
        );

        Gate::define(
            'users.create',
            fn (User $user): bool =>
                $user->hasPermissionTo('users.create', 'web'),
        );

        Gate::define(
            'users.update',
            fn (User $user): bool =>
                $user->hasPermissionTo('users.update', 'web'),
        );

        Gate::define(
            'users.delete',
            fn (User $user): bool =>
                $user->hasPermissionTo('users.delete', 'web'),
        );

        /*
         * اسم قديم للاعتماد النهائي، إن كانت ملفات أخرى تستخدمه.
         */
        Gate::define(
            'centers.final_approve',
            fn (User $user): bool =>
                $user->hasRole('super_admin'),
        );
    }
}
