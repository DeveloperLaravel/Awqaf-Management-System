<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_assessments', function (Blueprint $table) {
            $table->id();
           // المركز والطالب والحلقة
            $table->foreignId('center_id')
                ->constrained('centers')
                ->restrictOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->restrictOnDelete();

            $table->foreignId('circle_id')
                ->constrained('circles')
                ->restrictOnDelete();

            // المستخدم الذي أجرى التقييم
            $table->foreignId('assessed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // فترة التقييم الأسبوعية
            $table->date('week_start');
            $table->date('week_end');

            // درجات التقييم
            $table->decimal('memorization_score', 5, 2);
            $table->decimal('review_score', 5, 2);
            $table->decimal('tajweed_score', 5, 2);
            $table->decimal('attendance_score', 5, 2);
            $table->decimal('discipline_score', 5, 2);

            // النتيجة النهائية
            $table->decimal('total_score', 5, 2);
            $table->string('level', 30);

            $table->text('notes')->nullable();

            $table->timestamps();

            // تقييم واحد لكل طالب في المركز خلال الأسبوع
            $table->unique(
                ['center_id', 'student_id', 'week_start'],
                'student_assessments_center_student_week_unique'
            );

            $table->index(
                ['center_id', 'week_start'],
                'student_assessments_center_week_index'
            );

            $table->index(
                ['student_id', 'week_start'],
                'student_assessments_student_week_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_assessments');
    }
};
