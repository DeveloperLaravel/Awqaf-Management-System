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
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
              $table->foreignId('center_id')
        ->constrained('centers')
        ->restrictOnDelete();

    $table->string('full_name');
    $table->string('phone',30)->nullable();
    $table->string('email')->nullable();
    $table->string('national_id', 30)->nullable();
    $table->string('occupation')->nullable();
    $table->text('address')->nullable();
    $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['center_id', 'phone'],
                'guardians_center_phone_unique'
            );
               $table->index(['center_id', 'full_name']);
            $table->index(['center_id', 'is_active']);
        });
          // منع تكرار الرقم الوطني داخل المركز.
        DB::statement("
            CREATE UNIQUE INDEX guardians_center_national_id_unique
            ON guardians (center_id, national_id)
            WHERE national_id IS NOT NULL
        ");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
