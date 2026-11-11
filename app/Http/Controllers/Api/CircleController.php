<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Circle\StoreCircleRequest;
use App\Http\Requests\Circle\UpdateCircleRequest;
use App\Http\Resources\CircleResource;
use App\Models\Circle;
use App\Services\CircleAssignmentValidator;
use App\Services\CircleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CircleController extends Controller
{
    public function __construct(
        private readonly CircleService $circleService,
        private readonly CircleAssignmentValidator $assignmentValidator,
    ) {}

    /**
     * عرض الحلقات.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Circle::class);

        $filters = $request->validate([
            'center_id' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'level' => [
                'sometimes',
                'nullable',
                'in:beginner,intermediate,advanced',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $circles = $this->circleService->getCircles(
            $request->user(),
            $filters,
            (int) ($filters['per_page'] ?? 100)
        );

        return CircleResource::collection($circles)
            ->additional([
                'success' => true,
                'message' => 'تم جلب الحلقات بنجاح.',
            ]);
    }

    /**
     * عرض حلقة واحدة.
     */
    public function show(Circle $circle): CircleResource
    {
        Gate::authorize('view', $circle);

        $circle = $this->circleService->getCircle($circle);

        return new CircleResource($circle);
    }

    /**
     * إنشاء حلقة جديدة.
     */
    public function store(
        StoreCircleRequest $request
    ): JsonResponse {

        Gate::authorize('create', Circle::class);

        $data = $request->validated();

        // التحقق من نطاق المركز والمعلم.
        $this->assignmentValidator->validate(
            $request->user(),
            $data
        );

        $circle = $this->circleService->createCircle($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الحلقة بنجاح.',
            'circle' => new CircleResource($circle),
        ], 201);
    }

    /**
     * تعديل حلقة.
     */
    public function update(
        UpdateCircleRequest $request,
        Circle $circle
    ): JsonResponse {

        $user = $request->user();

        Gate::authorize('update', $circle);

        $data = $request->validated();

        // التحقق من صحة تعيين المعلم والمركز.
        $this->assignmentValidator->validate(
            $user,
            $data,
            $circle
        );

        $circle = $this->circleService->updateCircle(
            $circle,
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل الحلقة بنجاح.',
            'circle' => new CircleResource($circle),
        ]);
    }

    /**
     * حذف حلقة.
     */
    public function destroy(Circle $circle): JsonResponse
    {
        Gate::authorize('delete', $circle);

        $this->circleService->deleteCircle($circle);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الحلقة بنجاح.',
        ]);
    }
}
