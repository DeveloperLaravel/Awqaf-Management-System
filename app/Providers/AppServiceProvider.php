<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Center;
use App\Models\Circle;
use App\Models\Student;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\CenterHierarchy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\Teacher;
use App\Policies\CirclePolicy;
use App\Policies\StudentPolicy;
use App\Policies\TeacherPolicy;
use App\Models\Guardian;
use App\Policies\GuardianPolicy;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
         * المدير العام يتجاوز جميع فحوصات الصلاحيات
         * والأدوار المسجلة عبر Laravel Gate.
         */
Gate::policy(Guardian::class, GuardianPolicy::class);

Gate::policy(Student::class, StudentPolicy::class);




Gate::policy(Circle::class, CirclePolicy::class);
Gate::define(
    'circles.view_own',
    fn (User $user): bool =>
        $user->hasPermissionTo('circles.view_own', 'web')
);
Gate::define(
    'circles.view',
    fn (User $user): bool =>
        $user->hasPermissionTo('circles.view', 'web')
);
Gate::define(
    'circles.view',
    fn (User $user): bool =>
        $user->hasPermissionTo('circles.view', 'web')
);
Gate::define(
    'teachers.view',
    fn (User $user): bool =>
        $user->hasPermissionTo('circles.view', 'web')
);


Gate::define(
    'circles.create',
    fn (User $user): bool =>
        $user->hasPermissionTo('circles.create', 'web')
);

Gate::define(
    'circles.update',
    fn (User $user): bool =>
        $user->hasPermissionTo('circles.update', 'web')
);

Gate::define(
    'circles.delete',
    fn (User $user): bool =>
        $user->hasPermissionTo('circles.delete', 'web')
);



    /*
         * صلاحيات عرض المراكز.
         */
        Gate::define('centers.view', function (User $user): bool {
            return $user->hasPermissionTo('centers.view', 'web');
        });

        Gate::define('centers.view_all', function (User $user): bool {
            return $user->hasPermissionTo('centers.view_all', 'web');
        });

        /*
         * صلاحيات الإنشاء.
         */
        Gate::define('centers.create', function (User $user): bool {
            return $user->hasPermissionTo('centers.create', 'web');
        });

        Gate::define('centers.create_child', function (User $user): bool {
            return $user->hasPermissionTo('centers.create_child', 'web')
                && app(CenterHierarchy::class)
                    ->directlyManagedCenters($user)
                    ->exists();
        });













Gate::policy(Teacher::class, TeacherPolicy::class);

Gate::define(
    'teachers.view',
    fn (User $user): bool =>
        $user->hasPermissionTo('teachers.view', 'web')
);

Gate::define(
    'teachers.create',
    fn (User $user): bool =>
        $user->hasPermissionTo('teachers.create', 'web')
);

Gate::define(
    'teachers.update',
    fn (User $user): bool =>
        $user->hasPermissionTo('teachers.update', 'web')
);

Gate::define(
    'teachers.delete',
    fn (User $user): bool =>
        $user->hasPermissionTo('teachers.delete', 'web')
);

Gate::define(
    'teachers.manage_all',
    fn (User $user): bool =>
        $user->hasRole('super_admin')
        && $user->hasPermissionTo('teachers.manage_all', 'web')
);

    /*
         * صلاحيات عرض المراكز.
         */
        Gate::define('centers.view', function (User $user): bool {
            return $user->hasPermissionTo('centers.view', 'web');
        });

        Gate::define('centers.view_all', function (User $user): bool {
            return $user->hasPermissionTo('centers.view_all', 'web');
        });

        /*
         * صلاحيات الإنشاء.
         */
        Gate::define('centers.create', function (User $user): bool {
            return $user->hasPermissionTo('centers.create', 'web');
        });

        Gate::define('centers.create_child', function (User $user): bool {
            return $user->hasPermissionTo('centers.create_child', 'web')
                && app(CenterHierarchy::class)
                    ->directlyManagedCenters($user)
                    ->exists();
        });


             /* موافقة مدير المركز الأب:
     * يجب أن يمتلك صلاحية تعديل المراكز.
     * ويُتحقق من علاقته بالمركز الأب داخل CentersTable.
     */
    Gate::define(
        'centers.approve_child',
        fn (User $user): bool =>
            $user->hasPermissionTo('Update:Center', 'web')
    );
        /*
     * الاعتماد النهائي محصور بالمدير العام.
     */
    Gate::define(
        'centers.approve_final',
        fn (User $user): bool =>
            $user->hasRole('super_admin')
    );

        /*
         * تعديل وحذف المراكز.
         */
        Gate::define('centers.update', function (User $user): bool {
            return $user->hasPermissionTo('centers.update', 'web');
        });

        Gate::define('centers.delete', function (User $user): bool {
            return $user->hasPermissionTo('centers.delete', 'web');
        });

        /*
         * فحص إمكانية الوصول إلى مركز محدد.
         */
        Gate::define(
            'centers.access',
            function (User $user, Center $center): bool {
                if ($user->hasRole('super_admin')) {
                    return $user->can('centers.view_all');
                }

                return app(CenterHierarchy::class)->canUserAccess(
                    $user,
                    (int) $center->getKey(),
                );
            },
        );

        /*
         * عمليات الحذف والاستعادة المتقدمة.
         * لا تستخدمها إلا إذا كانت الصلاحيات معرفة في قاعدة البيانات.
         */
        Gate::define('centers.delete_any', function (User $user): bool {
            return $user->hasPermissionTo('centers.delete_any', 'web')
                && $user->can('centers.view_all');
        });

        Gate::define('centers.restore', function (User $user): bool {
            return $user->hasPermissionTo('centers.restore', 'web');
        });

        Gate::define('centers.force_delete', function (User $user): bool {
            return $user->hasPermissionTo('centers.force_delete', 'web');
        });

        Gate::define('centers.restore_any', function (User $user): bool {
            return $user->hasPermissionTo('centers.restore_any', 'web')
                && $user->can('centers.view_all');
        });

        Gate::define(
            'centers.force_delete_any',
            function (User $user): bool {
                return $user->hasPermissionTo(
                    'centers.force_delete_any',
                    'web',
                ) && $user->can('centers.view_all');
            },
        );

        Gate::define(
            'centers.replicate',
            function (User $user): bool {
                return $user->hasPermissionTo('centers.replicate', 'web');
            },
        );

        Gate::define(
            'centers.reorder',
            function (User $user): bool {
                return $user->hasPermissionTo('centers.reorder', 'web')
                    && $user->can('centers.view_all');
            },
        );

        // تسجيل سياسة المستخدمين.
        Gate::policy(User::class, UserPolicy::class);

        // صلاحيات المستخدمين.
        Gate::define(
            'users.view_all',
            fn (User $user): bool => $user->hasPermissionTo('users.view_all')
        );

        Gate::define(
            'users.view',
            fn (User $user): bool => $user->hasPermissionTo('users.view')
        );

        Gate::define(
            'users.create',
            fn (User $user): bool => $user->hasPermissionTo('users.create')
        );

        Gate::define(
            'users.update',
            fn (User $user): bool => $user->hasPermissionTo('users.update')
        );

        Gate::define(
            'users.delete',
            fn (User $user): bool => $user->hasPermissionTo('users.delete')
        );

        // صلاحيات المراكز.
        Gate::define(
            'centers.create',
            fn (User $user): bool => $user->hasPermissionTo('centers.create')
        );

        Gate::define(
            'centers.update',
            fn (User $user): bool => $user->hasPermissionTo('centers.update')
        );

        Gate::define(
            'centers.delete',
            fn (User $user): bool => $user->hasPermissionTo('centers.delete')
        );

        Gate::define(
            'centers.create_child',
            fn (User $user): bool => $user->hasPermissionTo('centers.create_child')
        );

        Gate::define(
            'centers.approve_child',
            fn (User $user): bool => $user->hasPermissionTo('centers.approve_child')
        );

        Gate::define(
            'centers.final_approve',
            fn (User $user): bool => $user->hasPermissionTo('centers.final_approve')
        );
    }
}
