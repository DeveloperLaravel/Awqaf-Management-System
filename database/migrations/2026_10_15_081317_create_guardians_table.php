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
        ->cascadeOnDelete();

    $table->string('full_name');
    $table->string('phone');
    $table->string('email')->nullable();
    $table->text('address')->nullable();
    $table->string('relationship')->nullable();
    $table->boolean('is_active')->default(true);

            $table->timestamps();
               $table->index(['center_id', 'full_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
