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
        Schema::table('centers', function (Blueprint $table) {
              $table->foreignId('region_id')
                ->nullable()
                ->after('name')
                ->constrained('regions')
                ->nullOnDelete();
                 $table->index(['region_id', 'is_active']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table) {
             $table->dropIndex(['region_id', 'is_active']);
            $table->dropConstrainedForeignId('region_id');
        });
    }
};
