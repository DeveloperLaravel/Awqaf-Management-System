
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circle_student', function (Blueprint $table) {
            $table->id();

            $table->foreignId('center_id')
                ->constrained('centers')
                ->restrictOnDelete();

            $table->unsignedBigInteger('circle_id');

            $table->unsignedBigInteger('student_id');

            $table->date('joined_at')->nullable();

            $table->date('left_at')->nullable();

            $table->timestamps();

            // التأكد أن الحلقة تتبع المركز نفسه.
            $table->foreign(
                ['circle_id', 'center_id'],
                'circle_student_circle_center_fk'
            )
                ->references(['id', 'center_id'])
                ->on('circles')
                ->restrictOnDelete();

            // التأكد أن الطالب يتبع المركز نفسه.
            $table->foreign(
                ['student_id', 'center_id'],
                'circle_student_student_center_fk'
            )
                ->references(['id', 'center_id'])
                ->on('students')
                ->restrictOnDelete();

            $table->index(
                ['student_id', 'center_id'],
                'circle_student_student_center_index'
            );

            $table->index(
                ['circle_id', 'center_id'],
                'circle_student_circle_center_index'
            );
        });

        // حلقة نشطة واحدة فقط لكل طالب.
        DB::statement("
            CREATE UNIQUE INDEX circle_student_one_active
            ON circle_student (student_id)
            WHERE left_at IS NULL
        ");

        // التحقق من صحة التواريخ.
        DB::statement("
            ALTER TABLE circle_student
            ADD CONSTRAINT circle_student_valid_dates
            CHECK (
                joined_at IS NULL
                OR left_at IS NULL
                OR left_at >= joined_at
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('circle_student');
    }
};
