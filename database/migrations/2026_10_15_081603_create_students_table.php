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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
             $table->foreignId('center_id')
        ->constrained('centers')
        ->restrictOnDelete();


    $table->string('student_code',50);
    $table->string('full_name');
    $table->date('birth_date')->nullable();
    $table->date('enrollment_date')->nullable();
    $table->string('gender', 20)->nullable()->comment('Gender: male or female');
    $table->string('school_grade')->nullable();
        $table->string('phone', 30)->nullable();
        $table->text('address')->nullable();
    $table->text('notes')->nullable();
    $table->boolean('is_active')->default(true);
      

            $table->timestamps();
            $table->unique(
    ['center_id', 'student_code'],
    'students_center_code_unique'
);
            $table->unique(
    ['id', 'center_id'],
    'students_id_center_unique'
);
             // فهرس مساعد للبحث.
            $table->index(
                ['center_id', 'full_name'],
                'students_center_name_index'
            );
                  $table->index(
                ['center_id', 'is_active'],
                'students_center_active_index'
            );
            $table->index('birth_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
