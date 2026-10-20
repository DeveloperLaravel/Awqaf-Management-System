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
        ->cascadeOnDelete();

    $table->foreignId('guardian_id')
        ->nullable()
        ->constrained('guardians')
        ->nullOnDelete();

    $table->string('student_code');
    $table->string('full_name');
    $table->date('birth_date')->nullable();
    $table->date('enrollment_date')->nullable();
    $table->string('gender', 20)->nullable();
    $table->string('school_grade')->nullable();
    $table->text('notes')->nullable();
    $table->boolean('is_active')->default(true);

            $table->timestamps();
                $table->unique(['center_id', 'student_code']);
    $table->index(['center_id', 'full_name']);
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
