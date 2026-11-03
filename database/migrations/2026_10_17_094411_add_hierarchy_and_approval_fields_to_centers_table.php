<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // علاقة المركز الأب
        if (! Schema::hasColumn('centers', 'parent_center_id')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->foreignId('parent_center_id')
                    ->nullable()
                    ->constrained('centers')
                    ->restrictOnDelete();
            });
        }

        // حالة اعتماد المركز
        if (! Schema::hasColumn('centers', 'approval_status')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->string('approval_status', 40)
                    ->default('pending_parent_approval');
            });

            // المراكز الرئيسية القديمة
            DB::table('centers')
                ->whereNull('parent_center_id')
                ->update([
                    'approval_status' => 'active',
                ]);

            // الفروع القديمة النشطة
            DB::table('centers')
                ->whereNotNull('parent_center_id')
                ->where('is_active', true)
                ->update([
                    'approval_status' => 'active',
                ]);
        }

        // موافقة المركز الأب
        if (! Schema::hasColumn('centers', 'parent_approved_by')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->foreignId('parent_approved_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('centers', 'parent_approved_at')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->timestamp('parent_approved_at')
                    ->nullable();
            });
        }

        // موافقة المدير العام
        if (! Schema::hasColumn('centers', 'approved_by')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->foreignId('approved_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('centers', 'approved_at')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->timestamp('approved_at')
                    ->nullable();
            });
        }

        // سبب الرفض
        if (! Schema::hasColumn('centers', 'rejection_reason')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->text('rejection_reason')
                    ->nullable();
            });
        }

        // فهرس علاقة الأب والحالة
        $indexExists = DB::table('pg_indexes')
            ->where('schemaname', 'public')
            ->where('tablename', 'centers')
            ->where('indexname', 'centers_parent_active_index')
            ->exists();

        if (
            Schema::hasColumn('centers', 'parent_center_id')
            && ! $indexExists
        ) {
            Schema::table('centers', function (Blueprint $table) {
                $table->index(
                    ['parent_center_id', 'is_active'],
                    'centers_parent_active_index'
                );
            });
        }
    }

    public function down(): void
    {
        // لا نحذف الأعمدة أو البيانات عند التراجع.
    }
};
