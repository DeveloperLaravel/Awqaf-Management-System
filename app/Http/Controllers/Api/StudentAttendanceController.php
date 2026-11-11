<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // الطالب المرتبط بحساب المستخدم الحالي فقط.
        $student = $user->student;

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد ملف طالب مرتبط بهذا الحساب.',
            ], 404);
        }

        $records = $student->attendanceRecords()
            ->with('circle:id,name')
            ->orderByDesc('attendance_date')
            ->get()
            ->map(fn ($record) => [
                'id' => $record->id,
                'attendance_date' => $record->attendance_date,
                'status' => $record->status,
                'notes' => $record->notes,
                'circle' => $record->circle ? [
                    'id' => $record->circle->id,
                    'name' => $record->circle->name,
                ] : null,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب سجل الحضور بنجاح.',
            'attendance' => $records->values(),
        ]);
    }
}
