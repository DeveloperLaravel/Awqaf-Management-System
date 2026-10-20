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
        Schema::create('memorization_records', function (Blueprint $table) {
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

    $table->date('record_date');

    $table->string('record_type', 20);
    // new_memorization, revision

    $table->string('surah_name');
    $table->unsignedSmallInteger('from_ayah');
    $table->unsignedSmallInteger('to_ayah');

    $table->decimal('grade', 5, 2)->nullable();
    $table->text('notes')->nullable();

            $table->timestamps();
              $table->index(['center_id', 'record_date']);
    $table->index(['student_id', 'record_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memorization_records');
    }
};
