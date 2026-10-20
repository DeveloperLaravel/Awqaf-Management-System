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
        ->cascadeOnDelete();

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
    $table->string('days')->nullable();
    $table->boolean('is_active')->default(true);

            $table->timestamps();
             $table->unique(['center_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circles');
    }
};
