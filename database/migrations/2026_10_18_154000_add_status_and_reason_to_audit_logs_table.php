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
        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'status')) {
                $table->string('status', 32)->default('SUCCESS')->after('action')->index();
            }
            if (!Schema::hasColumn('audit_logs', 'reason')) {
                $table->string('reason', 500)->nullable()->after('payload_diff');
            }
            $table->index(['action', 'created_at'], 'audit_logs_action_time_idx');
            $table->index(['ip_address', 'created_at'], 'audit_logs_ip_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_action_time_idx');
            $table->dropIndex('audit_logs_ip_time_idx');
            if (Schema::hasColumn('audit_logs', 'reason')) {
                $table->dropColumn('reason');
            }
            if (Schema::hasColumn('audit_logs', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
