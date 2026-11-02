<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AttendanceRecord;
use App\Models\Center;
use App\Models\Circle;
use App\Models\Guardian;
use App\Models\Region;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;

use App\Policies\AttendanceRecordPolicy;
use App\Policies\CirclePolicy;
use App\Policies\GuardianPolicy;
use App\Policies\RegionPolicy;
use App\Policies\StudentPolicy;
use App\Policies\TeacherPolicy;
use App\Policies\UserPolicy;

use App\Services\CenterHierarchy;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;
use App\Policies\CenterPolicy;

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

Gate::policy(AttendanceRecord::class, AttendanceRecordPolicy::class);
Gate::policy(Guardian::class, GuardianPolicy::class);
Gate::policy(Student::class, StudentPolicy::class);
Gate::policy(Circle::class, CirclePolicy::class);
Gate::policy(Teacher::class, TeacherPolicy::class);


Gate::policy(Center::class, CenterPolicy::class);
Gate::policy(User::class, UserPolicy::class);
Gate::policy(Region::class, RegionPolicy::class);
Gate::policy(Role::class, RolePolicy::class);
/*
 * صلاحيات المناطق.
 */




     
    }
}
