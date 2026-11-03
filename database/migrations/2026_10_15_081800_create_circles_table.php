<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circles', function (Blueprint $table) {

            $table->id();

            // المركز التابع له الحلقة
            $table->foreignId('center_id')
                ->constrained('centers')
                ->restrictOnDelete();

            // المعلم المرتبط بالمركز من خلال center_teacher
            $table->unsignedBigInteger('teacher_id')->nullable();

            $table->string('name');
            $table->string('code');

            $table->string('level')->nullable();
            $table->string('room')->nullable();

            $table->unsignedSmallInteger('capacity')->default(20);

            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();

            $table->json('days')->nullable();

            $table->boolean('is_active')->default(true);

            $table->text('description')->nullable();

            $table->timestamps();

            // ضمان أن المعلم مرتبط بنفس المركز
            $table->foreign(
                ['center_id', 'teacher_id'],
                'circles_center_teacher_fk'
            )
                ->references(['center_id', 'teacher_id'])
                ->on('center_teacher')
                ->restrictOnDelete();

            // منع تكرار رمز الحلقة داخل المركز
            $table->unique(
                ['center_id', 'code'],
                'circles_center_code_unique'
            );

            // مفتاح مركب لدعم الربط مع circle_student
            $table->unique(
                ['id', 'center_id'],
                'circles_id_center_unique'
            );

            $table->index(
                ['center_id', 'is_active'],
                'circles_center_active_index'
            );

            $table->index(
                ['center_id', 'teacher_id'],
                'circles_center_teacher_index'
            );
        });

        // الطاقة الاستيعابية يجب أن تكون أكبر من صفر
        DB::statement("
            ALTER TABLE circles
            ADD CONSTRAINT circles_capacity_positive
            CHECK (capacity > 0)
        ");

        // وقت البداية يجب أن يسبق وقت النهاية
        DB::statement("
            ALTER TABLE circles
            ADD CONSTRAINT circles_valid_schedule
            CHECK (
                starts_at IS NULL
                OR ends_at IS NULL
                OR starts_at < ends_at
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('circles');
    }
};