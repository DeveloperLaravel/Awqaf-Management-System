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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
             $table->foreignId('center_id')
        ->constrained('centers')
        ->restrictOnDelete();

    $table->foreignId('student_id')
        ->constrained('students')
        ->restrictOnDelete();

    $table->foreignId('received_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->string('receipt_number');
    $table->decimal('amount', 10, 2);

    $table->date('paid_at');
    $table->string('payment_method', 30);
    // cash, bank_transfer, electronic

    $table->string('fee_period')->nullable();
    $table->text('notes')->nullable();

            $table->timestamps();
             $table->unique(['center_id', 'receipt_number']);
    $table->index(['center_id', 'paid_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
