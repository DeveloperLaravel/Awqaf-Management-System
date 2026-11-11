
<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\CircleController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\StudentAttendanceController;
use App\Http\Controllers\Api\MemorizationRecordController;
Route::prefix('v1')->group(function () {

    // تسجيل الدخول
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    // المسارات المحمية بواسطة Sanctum
    Route::middleware('auth:sanctum')->group(function () {

        // المستخدم وتسجيل الخروج
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);

        /*
        |--------------------------------------------------------------------------
        | الطلاب
        |--------------------------------------------------------------------------
        */
        Route::get('/students', [StudentController::class, 'index']);
        Route::get('/students/{student}', [StudentController::class, 'show']);



          Route::get(
            '/memorization-records/options',
            [MemorizationRecordController::class, 'options']
        )->name('memorization-records.options');

        Route::apiResource(
            'memorization-records',
            MemorizationRecordController::class
        );





        /*
        |--------------------------------------------------------------------------
        | المعلمون
        |--------------------------------------------------------------------------
        */
        Route::get('/teachers', [TeacherController::class, 'index']);
        Route::get('/teachers/{teacher}', [TeacherController::class, 'show']);

        /*
        |--------------------------------------------------------------------------
        | الحلقات
        |--------------------------------------------------------------------------
        */
        Route::prefix('circles')->group(function () {

            Route::get('/', [CircleController::class, 'index']);

            Route::get('/{circle}', [CircleController::class, 'show']);

            Route::post('/', [CircleController::class, 'store']);

            Route::put('/{circle}', [CircleController::class, 'update']);

            Route::patch('/{circle}', [CircleController::class, 'update']);

            Route::delete('/{circle}', [CircleController::class, 'destroy']);

        });

        /*
        |--------------------------------------------------------------------------
        | حضور الطالب
        |--------------------------------------------------------------------------
        */
        Route::get('/my-attendance', [
            StudentAttendanceController::class,
            'index',
        ]);

        /*
        |--------------------------------------------------------------------------
        | سجلات الحضور
        |--------------------------------------------------------------------------
        */
        Route::prefix('attendance-records')->group(function () {

            // عرض سجلات الحضور
            Route::get('/', [AttendanceController::class, 'index']);

            // الحلقات المسموح للمستخدم بتسجيل الحضور فيها
            Route::get('/circles', [AttendanceController::class, 'circles']);

            // الطلاب المسجلون في حلقة محددة
            Route::get('/circles/{circle}/students', [
                AttendanceController::class,
                'students',
            ]);

            // تسجيل الحضور
            Route::post('/', [AttendanceController::class, 'store']);

        });

    });

});