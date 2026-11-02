<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('centers', 'parent_center_id')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->foreignId('parent_center_id')
                    ->nullable()
                    ->constrained('centers')
                    ->restrictOnDelete();
            });
        }

        if (!Schema::hasColumn('centers', 'approval_status')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->string('approval_status', 40)
                    ->default('pending_parent_approval');
            });

            DB::table('centers')
                ->whereNull('parent_center_id')
                ->update(['approval_status' => 'active']);

            DB::table('centers')
                ->whereNotNull('parent_center_id')
                ->where('is_active', true)
                ->update(['approval_status' => 'active']);
        }

        if (!Schema::hasColumn('centers', 'parent_approved_by')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->foreignId('parent_approved_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('centers', 'parent_approved_at')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->timestamp('parent_approved_at')->nullable();
            });
        }

        if (!Schema::hasColumn('centers', 'approved_by')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->foreignId('approved_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('centers', 'approved_at')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable();
            });
        }

        if (!Schema::hasColumn('centers', 'rejection_reason')) {
            Schema::table('centers', function (Blueprint $table) {
                $table->text('rejection_reason')->nullable();
            });
        }

        if (!Schema::hasColumn('centers', 'parent_center_id')) {
            // لا ينفذ عادة؛ الفهرس يعتمد على وجود العمود.
        } else {
            Schema::table('centers', function (Blueprint $table) {
                $table->index(
                    ['parent_center_id', 'is_active'],
                    'centers_parent_active_index'
                );
            });
        }

        Schema::table('centers', function (Blueprint $table) {
            // فهرس الحالة إذا لم يكن موجوداً مسبقاً
        });
    }

    public function down(): void
    {
        // الحفاظ على البيانات والأعمدة عند التراجع.
    }
};