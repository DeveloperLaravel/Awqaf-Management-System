<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('center_id');
            $table->unsignedBigInteger('guardian_id');
            $table->unsignedBigInteger('student_id');

            // صلة القرابة: أب، أم، أخ، عم...
            $table->string('relationship', 50)->nullable();

            // هل هذا ولي الأمر الأساسي؟
            $table->boolean('is_primary')->default(false);

            // السماح باستقبال الإشعارات.
            $table->boolean('can_receive_notifications')->default(true);

            // السماح باستلام الطالب.
            $table->boolean('can_pickup')->default(false);

            $table->timestamps();

            // المركز.
            $table->foreign(
                'center_id',
                'guardian_student_center_fk'
            )
                ->references('id')
                ->on('centers')
                ->restrictOnDelete();

            // ولي الأمر يجب أن يتبع المركز نفسه.
            $table->foreign(
                ['guardian_id', 'center_id'],
                'guardian_student_guardian_center_fk'
            )
                ->references(['id', 'center_id'])
                ->on('guardians')
                ->restrictOnDelete();

            // الطالب يجب أن يتبع المركز نفسه.
            $table->foreign(
                ['student_id', 'center_id'],
                'guardian_student_student_center_fk'
            )
                ->references(['id', 'center_id'])
                ->on('students')
                ->restrictOnDelete();

            // منع تكرار علاقة ولي الأمر بالطالب.
            $table->unique(
                ['guardian_id', 'student_id'],
                'guardian_student_unique'
            );

            $table->index(
                ['student_id', 'center_id'],
                'guardian_student_student_center_index'
            );

            $table->index(
                ['guardian_id', 'center_id'],
                'guardian_student_guardian_center_index'
            );
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
