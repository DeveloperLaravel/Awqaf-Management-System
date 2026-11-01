<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {

    // تسجيل الدخول: 5 محاولات في الدقيقة.
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/user', [AuthController::class, 'user']);

        Route::post('/logout', [AuthController::class, 'logout']);

    });

});