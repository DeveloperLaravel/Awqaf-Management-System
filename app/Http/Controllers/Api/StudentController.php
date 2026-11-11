<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * عرض الطلاب حسب صلاحيات المستخدم.
     */
    public function index(
        Request $request,
        StudentService $studentService
    ) {
        $students = $studentService->getStudents(
            $request->user(),
            $request->query('search')
        );

        return response()->json([
            'success' => true,
            'data' => $students,
        ]);
    }

    /**
     * عرض بيانات طالب محدد.
     */
    public function show(
        Request $request,
        Student $student,
        StudentService $studentService
    ) {
        $user = $request->user();

        abort_unless(
            $studentService->canAccessStudent($user, $student),
            403,
            'ليس لديك صلاحية للوصول إلى بيانات هذا الطالب.'
        );

        return response()->json([
            'success' => true,
            'data' => $studentService->findStudent($student->id),
        ]);
    }
}
