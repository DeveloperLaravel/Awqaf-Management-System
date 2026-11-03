<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();

            $table->foreignId('center_id')
                ->constrained('centers')
                ->restrictOnDelete();

            $table->string('full_name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('national_id', 30)->nullable();
            $table->string('occupation')->nullable();
            $table->text('address')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();

            // مطلوب لدعم المفتاح الأجنبي المركب.
            $table->unique(
                ['id', 'center_id'],
                'guardians_id_center_unique'
            );

            // منع تكرار رقم الهاتف داخل المركز.
            $table->unique(
                ['center_id', 'phone'],
                'guardians_center_phone_unique'
            );

            $table->index(
                ['center_id', 'full_name'],
                'guardians_center_name_index'
            );

            $table->index(
                ['center_id', 'is_active'],
                'guardians_center_active_index'
            );
        });

        // منع تكرار الرقم الوطني داخل المركز.
        // يسمح بوجود أكثر من قيمة NULL.
        DB::statement("
            CREATE UNIQUE INDEX guardians_center_national_id_unique
            ON guardians (center_id, national_id)
            WHERE national_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
