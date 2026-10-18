<?php

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add fraud detection columns to conversions table
        Schema::table('conversions', function (Blueprint $table) {
            if (!Schema::hasColumn('conversions', 'fraud_risk_score')) {
                $table->unsignedSmallInteger('fraud_risk_score')->default(0)->after('commission_value');
            }
            if (!Schema::hasColumn('conversions', 'is_suspicious')) {
                $table->boolean('is_suspicious')->default(false)->after('fraud_risk_score');
            }
            if (!Schema::hasColumn('conversions', 'fraud_signals')) {
                $table->json('fraud_signals')->nullable()->after('is_suspicious');
            }
            if (!Schema::hasColumn('conversions', 'investigated_at')) {
                $table->timestamp('investigated_at')->nullable()->after('fraud_signals');
            }
            if (!Schema::hasColumn('conversions', 'investigated_by_user_id')) {
                $table->foreignUuid('investigated_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('investigated_at');
            }
            if (!Schema::hasColumn('conversions', 'investigation_notes')) {
                $table->text('investigation_notes')->nullable()->after('investigated_by_user_id');
            }

            $table->index(['is_suspicious', 'status'], 'conv_suspicious_status_idx');
            $table->index('fraud_risk_score', 'conv_fraud_score_idx');
        });

        // 2. Create fraud_detection_rules table for configurable thresholds
        if (!Schema::hasTable('fraud_detection_rules')) {
            Schema::create('fraud_detection_rules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('rule_key', 64)->unique();
                $table->string('name_ar', 150);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('threshold_value', 50);
                $table->unsignedSmallInteger('risk_weight')->default(25);
                $table->string('action', 32)->default('FLAG_SUSPICIOUS');
                $table->timestamps();
            });

            // Seed default rules
            $defaultRules = [
                [
                    'id' => (string) Str::uuid(),
                    'rule_key' => 'click_burst_limit',
                    'name_ar' => 'كثافة نقرات غير طبيعية خلال دقيقة واحدة',
                    'description' => 'رصد تدفق نقرات كثيف من نفس البصمة التقنية أو الزائر يتجاوز الحد المسموح في دقيقة.',
                    'is_active' => true,
                    'threshold_value' => '15',
                    'risk_weight' => 35,
                    'action' => 'FLAG_SUSPICIOUS',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => (string) Str::uuid(),
                    'rule_key' => 'rapid_conversion_seconds',
                    'name_ar' => 'سرعة تحويل فورية غير بشرية بعد النقرة',
                    'description' => 'تسجيل تحويل مبيعات خلال أقل من 3 ثوانٍ من نقرة الإسناد مما يشير لسلوك بوت آلي.',
                    'is_active' => true,
                    'threshold_value' => '3',
                    'risk_weight' => 45,
                    'action' => 'FLAG_SUSPICIOUS',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => (string) Str::uuid(),
                    'rule_key' => 'conversion_velocity_window',
                    'name_ar' => 'تكرار تحويلات متتالية لنفس الزائر في نافذة وجيزة',
                    'description' => 'تسجيل أكثر من تحويل لنفس الزائر خلال أقل من دقيقتين.',
                    'is_active' => true,
                    'threshold_value' => '120',
                    'risk_weight' => 30,
                    'action' => 'FLAG_SUSPICIOUS',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => (string) Str::uuid(),
                    'rule_key' => 'high_order_value_multiplier',
                    'name_ar' => 'مضاعف قيمة طلب شاذ مقارنة بمتوسط السلة',
                    'description' => 'قيمة الطلب تتجاوز 10 أضعاف متوسط سلة الشراء المقدر للحملة.',
                    'is_active' => true,
                    'threshold_value' => '10',
                    'risk_weight' => 25,
                    'action' => 'FLAG_SUSPICIOUS',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            DB::table('fraud_detection_rules')->insert($defaultRules);
        }

        // 3. Add RBAC permission for fraud management
        $permission = Permission::firstOrCreate(
            ['name' => 'conversions.manage_fraud'],
            [
                'id' => (string) Str::uuid(),
                'label' => 'إدارة كشف الاحتيال والتحويلات المشبوهة',
                'category' => 'التحويلات والعمولات',
                'description' => 'صلاحية فحص وتدقيق التحويلات المشبوهة وتبرئة الشبهة أو الرفض الأمني.',
            ]
        );

        $superAdminRole = Role::where('name', UserRole::SUPER_ADMIN->value)->first();
        if ($superAdminRole && $permission) {
            $exists = DB::table('permission_role')
                ->where('role_id', $superAdminRole->id)
                ->where('permission_id', $permission->id)
                ->exists();

            if (!$exists) {
                $row = [
                    'role_id' => $superAdminRole->id,
                    'permission_id' => $permission->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (Schema::hasColumn('permission_role', 'id')) {
                    $row['id'] = (string) Str::uuid();
                }
                DB::table('permission_role')->insert($row);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fraud_detection_rules');

        if (Schema::hasTable('conversions')) {
            Schema::table('conversions', function (Blueprint $table) {
                $table->dropIndex('conv_suspicious_status_idx');
                $table->dropIndex('conv_fraud_score_idx');
                $table->dropColumn([
                    'fraud_risk_score',
                    'is_suspicious',
                    'fraud_signals',
                    'investigated_at',
                    'investigated_by_user_id',
                    'investigation_notes',
                ]);
            });
        }
    }
};
