<?php

use App\Enums\BillingCycle;
use App\Enums\LedgerAccountType;
use App\Enums\SubscriptionPlanStatus;
use App\Enums\SubscriptionStatus;
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
     * Run the migrations for Phase 8: Premium Campaigns, Subscription Plans & Entitlements.
     */
    public function up(): void
    {
        // 1. Add Subscription Revenue account to Chart of Accounts if not exists
        $accountExists = DB::table('ledger_accounts')
            ->where('code', '4020_SUBSCRIPTION_REVENUE')
            ->exists();

        if (!$accountExists) {
            DB::table('ledger_accounts')->insert([
                'id' => (string) Str::uuid(),
                'code' => '4020_SUBSCRIPTION_REVENUE',
                'name_ar' => 'إيرادات اشتراكات باقات المسوقين',
                'name_en' => 'Marketer Subscription Plans Revenue',
                'type' => LedgerAccountType::REVENUE->value,
                'is_system' => true,
                'description' => 'الإيرادات التشغيلية المكتسبة للمنصة من رسوم اشتراكات باقات المسوقين المميزة.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Add Phase 8 RBAC Permissions
        $permissions = [
            [
                'name' => 'subscriptions.view',
                'label' => 'عرض خطط واشتراكات المسوقين',
                'category' => 'الاشتراكات والخطط المميزة',
                'description' => 'الاطلاع على باقات الاشتراك وأسعارها وسجلات اشتراكات المسوقين.',
            ],
            [
                'name' => 'subscriptions.manage',
                'label' => 'إدارة خطط اشتراكات المسوقين',
                'category' => 'الاشتراكات والخطط المميزة',
                'description' => 'إنشاء وتعديل وإيقاف خطط الاشتراك وأسعارها بالعملات المختلفة.',
            ],
            [
                'name' => 'campaigns.manage_premium',
                'label' => 'إدارة تمييز وأهلية الحملات المميزة',
                'category' => 'الحملات',
                'description' => 'تحديد الحملات كمميزة وتعيين الباقات المؤهلة للمشاركة فيها.',
            ],
        ];

        foreach ($permissions as $permData) {
            Permission::firstOrCreate(
                ['name' => $permData['name']],
                [
                    'label' => $permData['label'],
                    'category' => $permData['category'],
                    'description' => $permData['description'],
                ]
            );
        }

        // Assign to Super Admin and Operations Manager
        $superAdminRole = Role::where('name', UserRole::SUPER_ADMIN->value)->first();
        if ($superAdminRole) {
            foreach ($permissions as $p) {
                $perm = Permission::where('name', $p['name'])->first();
                if ($perm) {
                    $exists = DB::table('permission_role')
                        ->where('role_id', $superAdminRole->id)
                        ->where('permission_id', $perm->id)
                        ->exists();
                    if (!$exists) {
                        $row = [
                            'role_id' => $superAdminRole->id,
                            'permission_id' => $perm->id,
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
        }

        $opsRole = Role::where('name', 'OPERATIONS_MANAGER')->first();
        if ($opsRole) {
            $viewPerm = Permission::where('name', 'subscriptions.view')->first();
            if ($viewPerm) {
                $exists = DB::table('permission_role')
                    ->where('role_id', $opsRole->id)
                    ->where('permission_id', $viewPerm->id)
                    ->exists();
                if (!$exists) {
                    $row = [
                        'role_id' => $opsRole->id,
                        'permission_id' => $viewPerm->id,
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

        // 3. Create subscription_plans table
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_ar', 150);
            $table->string('name_en', 150)->nullable();
            $table->string('slug', 150)->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('billing_cycle', 30)->default(BillingCycle::MONTHLY->value);
            $table->unsignedInteger('duration_in_days')->default(30);
            $table->string('status', 30)->default(SubscriptionPlanStatus::ACTIVE->value)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('allows_premium_campaigns')->default(true);
            $table->unsignedInteger('max_premium_campaigns_per_month')->nullable()->comment('Null = Unlimited');
            $table->unsignedInteger('max_active_applications')->nullable()->comment('Null = Unlimited');
            $table->json('features')->nullable();
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Create subscription_plan_prices table (Multi-currency without conversion)
        Schema::create('subscription_plan_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->string('currency', 10)->index();
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['subscription_plan_id', 'currency']);
        });

        // 5. Create marketer_subscriptions table
        Schema::create('marketer_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('subscription_number', 50)->unique();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('marketer_profile_id')->constrained('marketer_profiles')->cascadeOnDelete();
            $table->foreignUuid('subscription_plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->string('status', 30)->default(SubscriptionStatus::PENDING_PAYMENT->value)->index();
            $table->string('currency', 10);
            $table->decimal('price_paid', 10, 2);
            $table->string('billing_cycle', 30);
            $table->unsignedInteger('duration_in_days');
            $table->json('plan_snapshot')->comment('Immutable snapshot of plan details, entitlements, limits at purchase time');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->string('payment_method', 30)->default('GATEWAY')->comment('GATEWAY or WALLET');
            $table->string('payment_reference', 100)->nullable();
            $table->uuid('payment_deposit_id')->nullable();
            $table->uuid('ledger_transaction_id')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->string('idempotency_key', 150)->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['marketer_profile_id', 'status', 'ends_at']);
        });

        // 6. Add Premium Campaign columns to campaigns table
        Schema::table('campaigns', function (Blueprint $table) {
            $table->boolean('is_premium')->default(false)->after('is_featured')->index();
            $table->json('allowed_plan_ids')->nullable()->after('is_premium')->comment('Array of plan UUIDs or null for any premium plan');
            $table->text('premium_requirements')->nullable()->after('allowed_plan_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['is_premium', 'allowed_plan_ids', 'premium_requirements']);
        });

        Schema::dropIfExists('marketer_subscriptions');
        Schema::dropIfExists('subscription_plan_prices');
        Schema::dropIfExists('subscription_plans');
    }
};
