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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')
        ->constrained('centers')
        ->cascadeOnDelete();

    $table->foreignId('student_id')
        ->constrained('students')
        ->cascadeOnDelete();

    $table->foreignId('circle_id')
        ->constrained('circles')
        ->cascadeOnDelete();

    $table->foreignId('teacher_id')
        ->nullable()
        ->constrained('teachers')
        ->nullOnDelete();

    $table->date('attendance_date');

    $table->string('status', 20);
    // present, absent, late, excused

    $table->text('notes')->nullable();

    $table->timestamps();

    $table->unique(
        ['student_id', 'circle_id', 'attendance_date'],
        'attendance_student_circle_date_unique'
    );

    $table->index(['center_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
