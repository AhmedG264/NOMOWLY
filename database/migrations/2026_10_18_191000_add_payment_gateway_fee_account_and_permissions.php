<?php

use App\Enums\LedgerAccountType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations for Phase 6 ledger account and payment permissions.
     */
    public function up(): void
    {
        // 1. Add Payment Gateway Fee Expense Account to Chart of Accounts if not exists
        $exists = DB::table('ledger_accounts')
            ->where('code', '5030_PAYMENT_GATEWAY_FEE_EXPENSE')
            ->exists();

        if (!$exists) {
            DB::table('ledger_accounts')->insert([
                'id' => (string) Str::uuid(),
                'code' => '5030_PAYMENT_GATEWAY_FEE_EXPENSE',
                'name_ar' => 'مصروفات ورسوم بوابات الدفع الإلكتروني',
                'name_en' => 'Payment Gateway Processing Fees Expense',
                'type' => LedgerAccountType::EXPENSE->value,
                'is_system' => true,
                'description' => 'الرسوم المقتطعة من بوابات الدفع الإلكتروني (مدى، فيزا، ماستركارد، أبل باي) عند تحصيل الإيداعات.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Add Payment & Deposit Permissions
        $paymentPermissions = [
            [
                'name' => 'payments.view',
                'label' => 'عرض سجلات الإيداع والمدفوعات',
                'category' => 'المالية والمدفوعات',
                'description' => 'الاطلاع على عمليات شحن المحافظ والمدفوعات الإلكترونية وحالاتها.',
            ],
            [
                'name' => 'payments.manage',
                'label' => 'إدارة ومطابقة عمليات الدفع',
                'category' => 'المالية والمدفوعات',
                'description' => 'إدارة عمليات الدفع والتحقق من المزودين وتشغيل تقارير المطابقة المالية.',
            ],
            [
                'name' => 'payments.refund',
                'label' => 'إجراء واعتماد استرداد المدفوعات',
                'category' => 'المالية والمدفوعات',
                'description' => 'تنفيذ طلبات الاسترداد المالي الكامل والجزئي وترحيل قيودها المحاسبية.',
            ],
        ];

        foreach ($paymentPermissions as $permData) {
            $perm = Permission::firstOrCreate(
                ['name' => $permData['name']],
                [
                    'label' => $permData['label'],
                    'category' => $permData['category'],
                    'description' => $permData['description'],
                ]
            );

            // Assign to super_admin role if exists
            $superAdminRole = Role::where('name', 'super_admin')->first();
            if ($superAdminRole) {
                $superAdminRole->permissions()->syncWithoutDetaching([$perm->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('ledger_accounts')
            ->where('code', '5030_PAYMENT_GATEWAY_FEE_EXPENSE')
            ->delete();

        Permission::whereIn('name', ['payments.view', 'payments.manage', 'payments.refund'])->delete();
    }
};
