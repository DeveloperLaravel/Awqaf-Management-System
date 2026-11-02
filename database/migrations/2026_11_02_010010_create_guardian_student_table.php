<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();

            $table->foreignId('guardian_id')
                ->constrained('guardians')
                ->restrictOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->string('relationship', 50)->nullable();

            $table->boolean('is_primary')->default(false);

            $table->boolean('can_receive_notifications')
                ->default(true);

            $table->boolean('can_pickup')->default(false);

            $table->timestamps();

            $table->unique(
                ['guardian_id', 'student_id'],
                'guardian_student_unique'
            );
    $table->index('student_id');
            $table->index('guardian_id');
        });
          // ولي أمر أساسي واحد فقط لكل طالب.
        DB::statement("
            CREATE UNIQUE INDEX guardian_student_one_primary
            ON guardian_student (student_id)
            WHERE is_primary = TRUE
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_student');
    }
};
