<?php

use App\Enums\LedgerAccountType;
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
        // 1. Ledger Accounts (Chart of Accounts)
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('type', 32); // ASSET, LIABILITY, EQUITY, REVENUE, EXPENSE
            $table->boolean('is_system')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Wallets (Multi-currency, strictly isolated per owner & currency)
        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('wallet_type', 32); // BRAND, MARKETER, PLATFORM_INTERNAL
            $table->string('currency', 3); // EGP, SAR, AED, KWD, QAR, BHD, OMR, USD
            $table->decimal('available_balance', 18, 4)->default(0);
            $table->decimal('reserved_balance', 18, 4)->default(0);
            $table->string('status', 32)->default('ACTIVE'); // ACTIVE, FROZEN, CLOSED
            $table->timestamps();

            // Strict uniqueness: user cannot have duplicate wallets in the same currency
            $table->unique(['user_id', 'currency'], 'wallets_user_currency_unique');
            $table->index(['currency', 'wallet_type']);
        });

        // 3. Ledger Transactions (Double-entry transaction headers)
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transaction_number', 64)->unique();
            $table->string('currency', 3);
            $table->string('transaction_type', 32); // DEPOSIT, WITHDRAWAL, ESCROW_HOLD, ESCROW_RELEASE, COMMISSION_EARNING, PLATFORM_FEE, ADJUSTMENT, REVERSAL
            $table->string('status', 32)->default('POSTED'); // PENDING, POSTED, REVERSED, REJECTED
            $table->string('source_type', 64)->nullable();
            $table->string('source_id', 64)->nullable();
            $table->string('idempotency_key', 128)->unique();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->useCurrent();
            $table->foreignUuid('reversed_transaction_id')->nullable()->constrained('ledger_transactions')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->index(['currency', 'status']);
            $table->index(['source_type', 'source_id']);
        });

        // 4. Ledger Entries (Double-entry line items: Debits & Credits)
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ledger_transaction_id')->constrained('ledger_transactions')->cascadeOnDelete();
            $table->foreignUuid('ledger_account_id')->constrained('ledger_accounts');
            $table->foreignUuid('wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->string('entry_type', 16); // DEBIT, CREDIT
            $table->decimal('amount', 18, 4);
            $table->string('currency', 3);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['ledger_transaction_id']);
            $table->index(['wallet_id']);
            $table->index(['ledger_account_id']);
        });

        // 5. Wallet Transactions (Audit ledger statement per wallet)
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignUuid('ledger_transaction_id')->constrained('ledger_transactions')->cascadeOnDelete();
            $table->foreignUuid('ledger_entry_id')->constrained('ledger_entries')->cascadeOnDelete();
            $table->string('entry_type', 16); // DEBIT, CREDIT
            $table->decimal('amount', 18, 4);
            $table->decimal('balance_after', 18, 4);
            $table->decimal('reserved_after', 18, 4);
            $table->string('currency', 3);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
        });

        // Seed initial system chart of accounts
        $accounts = [
            [
                'id' => (string) Str::uuid(),
                'code' => '1010_PLATFORM_CASH',
                'name_ar' => 'نقدية وأموال المنصة المحصلة (حساب وسيط)',
                'name_en' => 'Platform Cash & Clearing Asset',
                'type' => LedgerAccountType::ASSET->value,
                'is_system' => true,
                'description' => 'أصول نقدية فعلية محصلة ومودعة في الحسابات البنكية الوسيطة للمنصة.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => '2010_BRAND_WALLET_LIABILITY',
                'name_ar' => 'التزامات أرصدة محافظ العلامات التجارية',
                'name_en' => 'Brand Wallet Balances Liability',
                'type' => LedgerAccountType::LIABILITY->value,
                'is_system' => true,
                'description' => 'التزامات المنصة تجاه العلامات التجارية عن الأرصدة المتاحة في محافظهم.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => '2020_CAMPAIGN_ESCROW_LIABILITY',
                'name_ar' => 'أموال ميزانيات الحملات المحجوزة بالضمان (Escrow)',
                'name_en' => 'Campaign Budget Escrow Liability',
                'type' => LedgerAccountType::LIABILITY->value,
                'is_system' => true,
                'description' => 'أموال العلامات التجارية المحجوزة كضمان للحملات التسويقية القائمة.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => '2030_MARKETER_PAYABLE_LIABILITY',
                'name_ar' => 'التزامات عمولات المسوقين المستحقة',
                'name_en' => 'Marketer Earned Commissions Payable',
                'type' => LedgerAccountType::LIABILITY->value,
                'is_system' => true,
                'description' => 'عمولات تسويقية مؤكدة ومستحقة للمسوقين لم يتم سحبها بعد.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => '3010_PLATFORM_EQUITY',
                'name_ar' => 'حقوق ملكية وتسويات المنصة',
                'name_en' => 'Platform Equity & Retained Earnings',
                'type' => LedgerAccountType::EQUITY->value,
                'is_system' => true,
                'description' => 'حقوق ملكية وتسويات رأس مال المنصة.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => '4010_PLATFORM_COMMISSION_REVENUE',
                'name_ar' => 'إيرادات عمولات ورسوم خدمة المنصة',
                'name_en' => 'Platform Service Commission Revenue',
                'type' => LedgerAccountType::REVENUE->value,
                'is_system' => true,
                'description' => 'الإيرادات التشغيلية المكتسبة للمنصة من رسوم الصفقات والتحويلات.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => '5010_PROMOTIONAL_EXPENSE',
                'name_ar' => 'مصروفات الأرصدة الترويجية والمنح',
                'name_en' => 'Promotional Credits & Grants Expense',
                'type' => LedgerAccountType::EXPENSE->value,
                'is_system' => true,
                'description' => 'تكاليف الأرصدة الترويجية والمكافآت الممنوحة غير النقدية.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => '5020_REFUND_SETTLEMENT_EXPENSE',
                'name_ar' => 'تسويات المبالغ المستردة والمصروفات الطارئة',
                'name_en' => 'Refund Settlements & Contingency Expense',
                'type' => LedgerAccountType::EXPENSE->value,
                'is_system' => true,
                'description' => 'المصروفات الناتجة عن تسويات الاسترداد أو النزاعات التجارية.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('ledger_accounts')->insert($accounts);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('ledger_accounts');
    }
};
