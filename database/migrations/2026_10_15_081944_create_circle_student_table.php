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
        Schema::create('circle_student', function (Blueprint $table) {
            $table->id();
             $table->foreignId('circle_id')
        ->constrained('circles')
        ->cascadeOnDelete();

    $table->foreignId('student_id')
        ->constrained('students')
        ->cascadeOnDelete();

    $table->date('joined_at')->nullable();
    $table->date('left_at')->nullable();

            $table->timestamps();
             $table->unique(['circle_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circle_student');
    }
};
