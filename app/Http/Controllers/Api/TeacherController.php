<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\TeacherService;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    /**
     * عرض قائمة المعلمين.
     */
    public function index(
        Request $request,
        TeacherService $teacherService
    ) {
        $teachers = $teacherService->getTeachers(
            $request->user(),
            $request->query('search')
        );

        return response()->json([
            'success' => true,
            'data' => $teachers,
        ]);
    }

    /**
     * عرض بيانات معلم محدد.
     */
    public function show(
        Request $request,
        Teacher $teacher,
        TeacherService $teacherService
    ) {
        $user = $request->user();

        abort_unless(
            $teacherService->canAccessTeacher($user, $teacher),
            403,
            'ليس لديك صلاحية الوصول إلى بيانات هذا المعلم.'
        );

        return response()->json([
            'success' => true,
            'data' => $teacherService->findTeacher($teacher->id),
        ]);
    }
}
