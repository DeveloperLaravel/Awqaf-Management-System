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
        Schema::table('memorization_records', function (Blueprint $table) {
           $table->decimal('tajweed_grade', 5, 2)
                ->nullable()
                ->after('grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memorization_records', function (Blueprint $table) {
            $table->dropColumn('tajweed_grade');
        });
    }
};
