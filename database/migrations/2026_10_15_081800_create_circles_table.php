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
        Schema::create('circles', function (Blueprint $table) {
            $table->id();
             $table->foreignId('center_id')
        ->constrained('centers')
        ->restrictOnDelete();

    $table->foreignId('teacher_id')
        ->nullable()
        ->constrained('teachers')
        ->nullOnDelete();

    $table->string('name');
    $table->string('code');
    $table->string('level')->nullable();
    $table->string('room')->nullable();
    $table->unsignedSmallInteger('capacity')->default(20);
    $table->time('starts_at')->nullable();
    $table->time('ends_at')->nullable();
    $table->json('days')->nullable();
    $table->boolean('is_active')->default(true);
     $table->text('description')->nullable();
   

            $table->timestamps();
               $table->unique(
                ['center_id', 'code'],
                'circles_center_code_unique'
            );
            $table->unique(
    ['id', 'center_id'],
    'circles_id_center_unique'
);
            $table->index(['center_id', 'is_active']);
            $table->index(['center_id', 'teacher_id']);
        });

         // التأكد من أن الطاقة الاستيعابية أكبر من صفر.
        DB::statement("
            ALTER TABLE circles
            ADD CONSTRAINT circles_capacity_positive
            CHECK (capacity > 0)
        ");
         DB::statement("
            ALTER TABLE circles
            ADD CONSTRAINT circles_valid_schedule
            CHECK (
                starts_at IS NULL
                OR ends_at IS NULL
                OR starts_at < ends_at
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circles');
    }
};
