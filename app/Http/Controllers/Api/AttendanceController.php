<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Circle;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService
    ) {}

    /**
     * GET /api/v1/attendance-records/circles
     */
  public function circles(Request $request)
{
    $user = $request->user();

    \Log::info('ATTENDANCE CIRCLES USER', [
        'user_id' => $user?->id,
        'roles' => $user?->getRoleNames()->toArray(),
    ]);

    $circles = $this->attendanceService->getCircles($user);

    \Log::info('ATTENDANCE CIRCLES RESULT', [
        'count' => $circles->count(),
        'data' => $circles->toArray(),
    ]);

    return response()->json([
        'success' => true,
        'data' => $circles,
    ]);
}

    /**
     * GET /api/v1/attendance-records/circles/{circle}/students
     */
    public function students(
        Request $request,
        Circle $circle
    ) {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => $this->attendanceService
                ->getStudents($user, $circle),
        ]);
    }

    /**
     * POST /api/v1/attendance-records
     */
    public function store(Request $request)
    {
        Gate::authorize('create', AttendanceRecord::class);

        $validated = $request->validate([
            'circle_id' => [
                'required',
                'integer',
                'exists:circles,id',
            ],

            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],

            'attendance_date' => [
                'required',
                'date',
            ],

            'status' => [
                'required',
                Rule::in([
                    AttendanceRecord::STATUS_PRESENT,
                    AttendanceRecord::STATUS_ABSENT,
                    AttendanceRecord::STATUS_LATE,
                    AttendanceRecord::STATUS_EXCUSED,
                ]),
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        /** @var User $user */
        $user = $request->user();

        $record = $this->attendanceService->store(
            $user,
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الحضور بنجاح.',
            'data' => $record,
        ], 201);
    }
}
