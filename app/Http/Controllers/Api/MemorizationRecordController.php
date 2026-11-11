<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemorizationRecordResource;
use App\Models\Center;
use App\Models\Circle;
use App\Models\MemorizationRecord;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CenterHierarchy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MemorizationRecordController extends Controller
{
    /**
     * المراكز النشطة التي يستطيع المستخدم الوصول إليها.
     */
    private function accessibleCenterIds(User $user): array
    {
        return app(CenterHierarchy::class)
            ->activeAccessibleIdsForUser($user);
    }

    /**
     * الاستعلام الأساسي مع تحديد نطاق المراكز.
     */
   private function recordsQuery(User $user): Builder
{
    $query = MemorizationRecord::query()
        ->with([
            'center:id,name',
            'student:id,full_name',
            'circle:id,name',
            'teacher:id,full_name',
        ]);

    // الطالب: يرى سجلاته فقط
    if ($user->hasRole('student')) {
        $student = $user->student;

        if (! $student) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('student_id', $student->id);
    }

    // المعلم: يرى السجلات التي سجلها فقط
    if ($user->hasRole('teacher')) {
        $teacher = $user->teacher;

        if (! $teacher) {
            return $query->whereRaw('1 = 0');
        }

        $activeCenterIds = $teacher->activeCenters()
            ->pluck('centers.id')
            ->all();

        return $query
            ->where('teacher_id', $teacher->id)
            ->whereIn('center_id', $activeCenterIds);
    }

    // مدير المركز والمدير العام
    $centerIds = $this->accessibleCenterIds($user);

    if (empty($centerIds)) {
        return $query->whereRaw('1 = 0');
    }

    return $query->whereIn('center_id', $centerIds);
}
    /**
     * التأكد من أن السجل تابع لمركز مسموح.
     */
    private function findRecord(User $user, int $id): MemorizationRecord
    {
        return $this->recordsQuery($user)->findOrFail($id);
    }

    /**
     * التحقق من صحة العلاقات التابعة للمركز.
     */
    private function validateRelations(
        array $data,
        int $centerId
    ): void {
        $studentExists = Student::query()
            ->whereKey($data['student_id'])
            ->where('center_id', $centerId)
            ->exists();

        if (! $studentExists) {
            throw ValidationException::withMessages([
                'student_id' => 'الطالب لا يتبع المركز المحدد.',
            ]);
        }

        if (! empty($data['circle_id'])) {
            $circleExists = Circle::query()
                ->whereKey($data['circle_id'])
                ->where('center_id', $centerId)
                ->where('is_active', true)
                ->exists();

            if (! $circleExists) {
                throw ValidationException::withMessages([
                    'circle_id' => 'الحلقة غير نشطة أو لا تتبع المركز المحدد.',
                ]);
            }
        }

        if (! empty($data['teacher_id'])) {
            $teacherExists = Teacher::query()
                ->whereKey($data['teacher_id'])
                ->whereHas('centers', function (Builder $query) use ($centerId) {
                    $query
                        ->where('centers.id', $centerId)
                        ->where('center_teacher.is_active', true);
                })
                ->exists();

            if (! $teacherExists) {
                throw ValidationException::withMessages([
                    'teacher_id' => 'المعلم غير مرتبط بالمركز المحدد أو أن ارتباطه غير نشط.',
                ]);
            }
        }
    }

    /**
     * قواعد التحقق من البيانات.
     */
    private function validationRules(
        array $centerIds,
        bool $updating = false
    ): array {
        $required = $updating ? 'sometimes' : 'required';

        return [
            'center_id' => [
                $required,
                'integer',
                Rule::in($centerIds),
                'exists:centers,id',
            ],

            'student_id' => [
                $required,
                'integer',
                'exists:students,id',
            ],

            'circle_id' => [
                'nullable',
                'integer',
                'exists:circles,id',
            ],

            'teacher_id' => [
                'nullable',
                'integer',
                'exists:teachers,id',
            ],

            'record_date' => [
                $required,
                'date',
            ],

            'record_type' => [
                $required,
                Rule::in(['new', 'review', 'revision']),
            ],

            'surah_name' => [
                $required,
                'string',
                'max:100',
            ],

            'from_ayah' => [
                $required,
                'integer',
                'min:1',
            ],

            'to_ayah' => [
                $required,
                'integer',
                'gte:from_ayah',
            ],

            'grade' => [
                'nullable',
                'numeric',
                'between:0,100',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    /**
     * عرض سجلات التسميع مع البحث والتصفية.
     *
     * GET /memorization-records
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        Gate::forUser($user)->authorize(
            'viewAny',
            MemorizationRecord::class
        );

        $centerIds = $this->accessibleCenterIds($user);

        if (empty($centerIds)) {
            return response()->json([
                'success' => true,
                'message' => 'لا توجد مراكز متاحة.',
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 0,
                ],
            ]);
        }

        $filters = $request->validate([
            'center_id' => [
                'nullable',
                'integer',
                Rule::in($centerIds),
            ],
            'student_id' => ['nullable', 'integer'],
            'circle_id' => ['nullable', 'integer'],
            'teacher_id' => ['nullable', 'integer'],
            'record_type' => [
                'nullable',
                Rule::in(['new', 'review', 'revision']),
            ],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $this->recordsQuery($user);

        $query
            ->when(
                $filters['center_id'] ?? null,
                fn (Builder $q, $id) => $q->where('center_id', $id)
            )
            ->when(
                $filters['student_id'] ?? null,
                fn (Builder $q, $id) => $q->where('student_id', $id)
            )
            ->when(
                $filters['circle_id'] ?? null,
                fn (Builder $q, $id) => $q->where('circle_id', $id)
            )
            ->when(
                $filters['teacher_id'] ?? null,
                fn (Builder $q, $id) => $q->where('teacher_id', $id)
            )
            ->when(
                $filters['record_type'] ?? null,
                fn (Builder $q, $type) => $q->where('record_type', $type)
            )
            ->when(
                $filters['from_date'] ?? null,
                fn (Builder $q, $date) => $q->whereDate('record_date', '>=', $date)
            )
            ->when(
                $filters['to_date'] ?? null,
                fn (Builder $q, $date) => $q->whereDate('record_date', '<=', $date)
            )
            ->when(
                $filters['search'] ?? null,
                function (Builder $q, $search) {
                    $q->where(function (Builder $sub) use ($search) {
                        $sub
                            ->where('surah_name', 'ilike', "%{$search}%")
                            ->orWhereHas('student', function (Builder $student) use ($search) {
                                $student->where('full_name', 'ilike', "%{$search}%");
                            });
                    });
                }
            );

        $records = $query
            ->latest('record_date')
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب سجلات التسميع بنجاح.',
            'data' => MemorizationRecordResource::collection(
                $records->getCollection()
            ),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
            'links' => [
                'first' => $records->url(1),
                'last' => $records->url($records->lastPage()),
                'prev' => $records->previousPageUrl(),
                'next' => $records->nextPageUrl(),
            ],
        ]);
    }

    /**
     * تسجيل تسميع جديد.
     *
     * POST /memorization-records
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        Gate::forUser($user)->authorize(
            'create',
            MemorizationRecord::class
        );

        $centerIds = $this->accessibleCenterIds($user);

        $data = $request->validate(
            $this->validationRules($centerIds)
        );

        $this->validateRelations(
            $data,
            (int) $data['center_id']
        );

        $record = DB::transaction(function () use ($data) {
            return MemorizationRecord::create($data);
        });

        $record->load([
            'center:id,name',
            'student:id,full_name',
            'circle:id,name',
            'teacher:id,full_name',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل التسميع بنجاح.',
            'data' => new MemorizationRecordResource($record),
        ], 201);
    }

    /**
     * عرض سجل واحد.
     *
     * GET /memorization-records/{id}
     */
    public function show(
        Request $request,
        int $id
    ): MemorizationRecordResource {
        $record = $this->findRecord($request->user(), $id);

        Gate::forUser($request->user())->authorize(
            'view',
            $record
        );

        return new MemorizationRecordResource($record);
    }

    /**
     * تعديل سجل تسميع.
     *
     * PUT /memorization-records/{id}
     */
    public function update(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        $record = $this->findRecord($user, $id);

        Gate::forUser($user)->authorize(
            'update',
            $record
        );

        $centerIds = $this->accessibleCenterIds($user);

        $data = $request->validate(
            $this->validationRules($centerIds, true)
        );

        $centerId = (int) (
            $data['center_id'] ?? $record->center_id
        );

        $studentId = $data['student_id'] ?? $record->student_id;

        $this->validateRelations(
            array_merge($data, [
                'student_id' => $studentId,
            ]),
            $centerId
        );

        $record = DB::transaction(function () use (
            $record,
            $data,
            $centerId
        ) {
            $record->fill($data);

            $record->center_id = $centerId;

            $record->save();

            return $record;
        });

        $record->load([
            'center:id,name',
            'student:id,full_name',
            'circle:id,name',
            'teacher:id,full_name',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث سجل التسميع بنجاح.',
            'data' => new MemorizationRecordResource($record),
        ]);
    }

    /**
     * حذف سجل تسميع.
     *
     * DELETE /memorization-records/{id}
     */
    public function destroy(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        $record = $this->findRecord($user, $id);

        Gate::forUser($user)->authorize(
            'delete',
            $record
        );

        DB::transaction(function () use ($record) {
            $record->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'تم حذف سجل التسميع بنجاح.',
        ]);
    }

    /**
     * بيانات الاختيارات للنموذج في Flutter.
     *
     * GET /memorization-records/options
     */
    public function options(Request $request): JsonResponse
    {
        $user = $request->user();

        Gate::forUser($user)->authorize(
            'create',
            MemorizationRecord::class
        );

        $centerIds = $this->accessibleCenterIds($user);

        $filters = $request->validate([
            'center_id' => [
                'nullable',
                'integer',
                Rule::in($centerIds),
            ],
            'search' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $centerId = $filters['center_id'] ?? null;
        $search = $filters['search'] ?? null;
        $limit = $filters['limit'] ?? 50;

        $centers = Center::query()
            ->whereIn('id', $centerIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $students = collect();
        $circles = collect();
        $teachers = collect();

        if ($centerId) {
            $students = Student::query()
                ->where('center_id', $centerId)
                ->when($search, fn (Builder $q) =>
                    $q->where('full_name', 'ilike', "%{$search}%")
                )
                ->orderBy('full_name')
                ->limit($limit)
                ->get(['id', 'full_name']);

            $circles = Circle::query()
                ->where('center_id', $centerId)
                ->where('is_active', true)
                ->when($search, fn (Builder $q) =>
                    $q->where('name', 'ilike', "%{$search}%")
                )
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'name']);

            $teachers = Teacher::query()
                ->whereHas('centers', function (Builder $q) use ($centerId) {
                    $q
                        ->where('centers.id', $centerId)
                        ->where('center_teacher.is_active', true);
                })
                ->when($search, fn (Builder $q) =>
                    $q->where('full_name', 'ilike', "%{$search}%")
                )
                ->orderBy('full_name')
                ->limit($limit)
                ->get(['id', 'full_name']);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات الاختيارات بنجاح.',
            'data' => [
                'centers' => $centers,
                'students' => $students,
                'circles' => $circles,
                'teachers' => $teachers,
            ],
        ]);
    }
}
