<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_teacher', function (Blueprint $table) {
            $table->id();

            $table->foreignId('center_id')
                ->constrained('centers')
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();

            $table->boolean('is_active')->default(true);

            $table->date('joined_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['center_id', 'teacher_id'],
                'center_teacher_unique'
            );

            $table->index(
                ['teacher_id', 'is_active'],
                'center_teacher_teacher_active_index'
            );
        });

        // نقل الارتباطات القديمة دون تكرار.
        DB::statement("
            INSERT INTO center_teacher (
                center_id,
                teacher_id,
                is_active,
                joined_at,
                created_at,
                updated_at
            )
            SELECT
                t.center_id,
                t.id,
                TRUE,
                t.hire_date,
                CURRENT_TIMESTAMP,
                CURRENT_TIMESTAMP
            FROM teachers t
            INNER JOIN centers c ON c.id = t.center_id
            WHERE t.center_id IS NOT NULL
            ON CONFLICT (center_id, teacher_id) DO NOTHING
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('center_teacher');
    }
};