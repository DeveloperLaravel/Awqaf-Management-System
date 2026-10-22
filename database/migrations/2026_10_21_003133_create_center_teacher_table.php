
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
        });

        // نقل ارتباط كل معلم بمركزه الحالي.
        $now = now();

        DB::table('teachers')
            ->whereNotNull('center_id')
            ->orderBy('id')
            ->select('id', 'center_id')
            ->chunk(500, function ($teachers) use ($now) {
                $rows = [];

                foreach ($teachers as $teacher) {
                    $rows[] = [
                        'center_id' => $teacher->center_id,
                        'teacher_id' => $teacher->id,
                        'is_active' => true,
                        'joined_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('center_teacher')
                        ->insertOrIgnore($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_teacher');
    }
};
