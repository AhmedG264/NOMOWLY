<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Enums\ConversionStatus;
use App\Enums\Currency;
use App\Enums\LedgerEntryType;
use App\Enums\LedgerTransactionStatus;
use App\Enums\LedgerTransactionType;
use App\Enums\UserRole;
use App\Enums\WalletStatus;
use App\Enums\WalletType;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\UnbalancedLedgerTransactionException;
use App\Models\AuditLog;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\Conversion;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\MarketerProfile;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Ledger\LedgerService;
use App\Services\Ledger\WalletService;
use App\Services\Math\MoneyMath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class Phase4FinancialLedgerAndWalletTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $brandUser;
    protected User $marketerUser;
    protected LedgerService $ledgerService;
    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed RBAC and accounts
        $this->artisan('migrate');

        $this->ledgerService = app(LedgerService::class);
        $this->walletService = app(WalletService::class);

        // Create test users
        $this->superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'must_change_password' => false,
        ]);

        $this->brandUser = User::factory()->create([
            'role' => UserRole::BRAND,
            'must_change_password' => false,
        ]);
        BrandProfile::create([
            'user_id' => $this->brandUser->id,
            'company_name' => 'شركة الرياض للتقنية',
        ]);

        $this->marketerUser = User::factory()->create([
            'role' => UserRole::MARKETER,
            'must_change_password' => false,
        ]);
        MarketerProfile::create([
            'user_id' => $this->marketerUser->id,
            'full_name' => 'أحمد المسوق',
            'phone' => '+966500000000',
            'country' => 'SA',
        ]);
    }

    /**
     * 1. إنشاء محفظة لكل عملة بصورة صحيحة
     */
    public function test_can_create_wallets_for_all_eight_currencies(): void
    {
        $allCurrencies = Currency::cases();
        $this->assertCount(8, $allCurrencies);

        foreach ($allCurrencies as $currency) {
            $wallet = $this->walletService->getOrCreateWallet($this->brandUser, $currency);

            $this->assertInstanceOf(Wallet::class, $wallet);
            $this->assertEquals($currency, $wallet->currency);
            $this->assertEquals('0.0000', $wallet->available_balance);
            $this->assertEquals('0.0000', $wallet->reserved_balance);
            $this->assertEquals(WalletStatus::ACTIVE, $wallet->status);
        }

        $wallets = $this->walletService->getWalletsForUser($this->brandUser);
        $this->assertCount(8, $wallets);
    }

    /**
     * 2. منع تكرار المحفظة عند وجوب التفرد
     */
    public function test_prevents_duplicate_wallet_for_same_user_and_currency(): void
    {
        $wallet1 = $this->walletService->getOrCreateWallet($this->brandUser, Currency::SAR);
        $wallet2 = $this->walletService->getOrCreateWallet($this->brandUser, Currency::SAR);

        $this->assertEquals($wallet1->id, $wallet2->id);
        $this->assertEquals(1, Wallet::where('user_id', $this->brandUser->id)->where('currency', 'SAR')->count());
    }

    /**
     * 3. منع خلط العملات
     */
    public function test_currency_isolation_prevents_cross_currency_transactions(): void
    {
        $sarWallet = $this->walletService->getOrCreateWallet($this->brandUser, Currency::SAR);

        // Attempting to post a USD transaction that references a SAR wallet must fail
        $this->expectException(InvalidArgumentException::class);
        $this->ledgerService->postTransaction([
            'currency' => Currency::USD,
            'transaction_type' => LedgerTransactionType::DEPOSIT,
            'idempotency_key' => 'mixed-currency-test',
            'description' => 'محاولة خلط عملات غير قانونية',
            'entries' => [
                [
                    'account_code' => '1010_PLATFORM_CASH',
                    'entry_type' => LedgerEntryType::DEBIT,
                    'amount' => '100.0000',
                ],
                [
                    'account_code' => '2010_BRAND_WALLET_LIABILITY',
                    'wallet_id' => $sarWallet->id, // SAR wallet in USD tx!
                    'entry_type' => LedgerEntryType::CREDIT,
                    'amount' => '100.0000',
                ],
            ],
        ]);
    }

    /**
     * 4. نجاح القيد المتوازن
     */
    public function test_balanced_double_entry_transaction_succeeds_and_updates_balances(): void
    {
        $wallet = $this->walletService->getOrCreateWallet($this->brandUser, Currency::SAR);

        $tx = $this->ledgerService->postTransaction([
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::DEPOSIT,
            'idempotency_key' => 'deposit-test-1',
            'description' => 'إيداع رصيد نقدي للعلامة التجارية',
            'created_by' => $this->superAdmin,
            'entries' => [
                [
                    'account_code' => '1010_PLATFORM_CASH',
                    'entry_type' => LedgerEntryType::DEBIT,
                    'amount' => '500.0000',
                ],
                [
                    'account_code' => '2010_BRAND_WALLET_LIABILITY',
                    'wallet_id' => $wallet->id,
                    'entry_type' => LedgerEntryType::CREDIT,
                    'amount' => '500.0000',
                ],
            ],
        ]);

        $this->assertInstanceOf(LedgerTransaction::class, $tx);
        $this->assertEquals(LedgerTransactionStatus::POSTED, $tx->status);
        $this->assertCount(2, $tx->entries);

        // Wallet balance must now reflect 500.0000 available
        $wallet->refresh();
        $this->assertEquals('500.0000', $wallet->available_balance);
        $this->assertEquals('0.0000', $wallet->reserved_balance);
        $this->assertEquals('500.0000', $wallet->total_balance);

        // Statement transaction must exist
        $this->assertEquals(1, $wallet->transactions()->count());
        $statementItem = $wallet->transactions()->first();
        $this->assertEquals('500.0000', $statementItem->balance_after);
    }

    /**
     * 5. رفض القيد غير المتوازن
     */
    public function test_unbalanced_double_entry_transaction_is_strictly_rejected(): void
    {
        $wallet = $this->walletService->getOrCreateWallet($this->brandUser, Currency::SAR);

        $this->expectException(UnbalancedLedgerTransactionException::class);

        $this->ledgerService->postTransaction([
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::DEPOSIT,
            'idempotency_key' => 'unbalanced-test-1',
            'description' => 'قيد غير متوازن مرفوض',
            'entries' => [
                [
                    'account_code' => '1010_PLATFORM_CASH',
                    'entry_type' => LedgerEntryType::DEBIT,
                    'amount' => '500.0000',
                ],
                [
                    'account_code' => '2010_BRAND_WALLET_LIABILITY',
                    'wallet_id' => $wallet->id,
                    'entry_type' => LedgerEntryType::CREDIT,
                    'amount' => '450.0000', // Unbalanced by 50.0000!
                ],
            ],
        ]);
    }

    /**
     * 6. منع تعديل أو حذف القيود المرحلة (Immutability)
     */
    public function test_posted_ledger_entries_are_completely_immutable(): void
    {
        $wallet = $this->walletService->getOrCreateWallet($this->brandUser, Currency::SAR);

        $tx = $this->ledgerService->postTransaction([
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::DEPOSIT,
            'idempotency_key' => 'immutable-test-1',
            'description' => 'اختبار عدم قابلية التعديل',
            'entries' => [
                ['account_code' => '1010_PLATFORM_CASH', 'entry_type' => LedgerEntryType::DEBIT, 'amount' => '200.0000'],
                ['account_code' => '2010_BRAND_WALLET_LIABILITY', 'wallet_id' => $wallet->id, 'entry_type' => LedgerEntryType::CREDIT, 'amount' => '200.0000'],
            ],
        ]);

        $entry = $tx->entries()->first();

        // Attempting to update entry must throw RuntimeException
        try {
            $entry->update(['amount' => '300.0000']);
            $this->fail('Expected RuntimeException on ledger entry update.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('غير قابلة للتعديل', $e->getMessage());
        }

        // Attempting to delete entry must throw RuntimeException
        try {
            $entry->delete();
            $this->fail('Expected RuntimeException on ledger entry delete.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('غير قابلة للحذف', $e->getMessage());
        }
    }

    /**
     * 7. نجاح القيد العكسي دون تغيير القيد الأصلي
     */
    public function test_reversal_transaction_creates_offsetting_entries_safely(): void
    {
        $wallet = $this->walletService->getOrCreateWallet($this->brandUser, Currency::SAR);

        // 1. Initial Deposit 300 SAR
        $originalTx = $this->ledgerService->postTransaction([
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::DEPOSIT,
            'idempotency_key' => 'tx-to-reverse',
            'description' => 'إيداع يراد عكسه',
            'entries' => [
                ['account_code' => '1010_PLATFORM_CASH', 'entry_type' => LedgerEntryType::DEBIT, 'amount' => '300.0000'],
                ['account_code' => '2010_BRAND_WALLET_LIABILITY', 'wallet_id' => $wallet->id, 'entry_type' => LedgerEntryType::CREDIT, 'amount' => '300.0000'],
            ],
        ]);

        $wallet->refresh();
        $this->assertEquals('300.0000', $wallet->available_balance);

        // 2. Perform Reversal
        $reversalTx = $this->ledgerService->reverseTransaction(
            $originalTx,
            'خطأ في التحويل البنكي',
            $this->superAdmin
        );

        $this->assertInstanceOf(LedgerTransaction::class, $reversalTx);
        $this->assertEquals(LedgerTransactionType::REVERSAL, $reversalTx->transaction_type);

        // Original transaction is marked reversed but its entries are unchanged
        $originalTx->refresh();
        $this->assertEquals(LedgerTransactionStatus::REVERSED, $originalTx->status);
        $this->assertEquals($reversalTx->id, $originalTx->reversed_transaction_id);

        // Wallet available balance returned to 0.0000
        $wallet->refresh();
        $this->assertEquals('0.0000', $wallet->available_balance);

        // Trial balance remains 100% balanced
        $tb = $this->ledgerService->getTrialBalance(Currency::SAR);
        $this->assertTrue($tb['SAR']['is_balanced']);
        $this->assertEquals('0.0000', $tb['SAR']['difference']);
    }

    /**
     * 8. منع الخصم المكرر (Idempotency Key Enforcement)
     */
    public function test_idempotency_key_prevents_duplicate_deduction(): void
    {
        $wallet = $this->walletService->getOrCreateWallet($this->brandUser, Currency::SAR);

        // Deposit 1000 SAR
        $this->ledgerService->postTransaction([
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::DEPOSIT,
            'idempotency_key' => 'funding-1000',
            'description' => 'شحن المحفظة',
            'entries' => [
                ['account_code' => '1010_PLATFORM_CASH', 'entry_type' => LedgerEntryType::DEBIT, 'amount' => '1000.0000'],
                ['account_code' => '2010_BRAND_WALLET_LIABILITY', 'wallet_id' => $wallet->id, 'entry_type' => LedgerEntryType::CREDIT, 'amount' => '1000.0000'],
            ],
        ]);

        $wallet->refresh();
        $this->assertEquals('1000.0000', $wallet->available_balance);

        $deductionParams = [
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::WITHDRAWAL,
            'idempotency_key' => 'deduct-payment-order-123',
            'description' => 'سحب مالي معتمد',
            'entries' => [
                ['account_code' => '2010_BRAND_WALLET_LIABILITY', 'wallet_id' => $wallet->id, 'entry_type' => LedgerEntryType::DEBIT, 'amount' => '300.0000'],
                ['account_code' => '1010_PLATFORM_CASH', 'entry_type' => LedgerEntryType::CREDIT, 'amount' => '300.0000'],
            ],
        ];

        // First call: deducts 300
        $tx1 = $this->ledgerService->postTransaction($deductionParams);

        $wallet->refresh();
        $this->assertEquals('700.0000', $wallet->available_balance);

        // Second call with same idempotency key: must NOT deduct again!
        $tx2 = $this->ledgerService->postTransaction($deductionParams);

        $this->assertEquals($tx1->id, $tx2->id);

        $wallet->refresh();
        $this->assertEquals('700.0000', $wallet->available_balance); // Balance unchanged!
    }

    /**
     * 9. حجز ميزانية الحملات (Escrow hold & release)
     */
    public function test_campaign_budget_escrow_hold_and_release(): void
    {
        $wallet = $this->walletService->getOrCreateWallet($this->brandUser, Currency::SAR);

        // Fund wallet with 2000 SAR
        $this->ledgerService->postTransaction([
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::DEPOSIT,
            'idempotency_key' => 'fund-brand-2000',
            'description' => 'إيداع أولي',
            'entries' => [
                ['account_code' => '1010_PLATFORM_CASH', 'entry_type' => LedgerEntryType::DEBIT, 'amount' => '2000.0000'],
                ['account_code' => '2010_BRAND_WALLET_LIABILITY', 'wallet_id' => $wallet->id, 'entry_type' => LedgerEntryType::CREDIT, 'amount' => '2000.0000'],
            ],
        ]);

        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandUser->brandProfile->id,
            'title' => 'حملة العيد للملابس',
            'destination_url' => 'https://example.com',
            'category' => 'أزياء',
            'description' => 'وصف حملة ملابس العيد بالرياض',
            'brief' => 'موجز تفصيلي لحملة ملابس العيد بالرياض',
            'target_markets' => ['SA'],
            'currencies' => ['SAR'],
            'tracking_methods' => ['LINK'],
            'commission_type' => CampaignCommissionType::FIXED_PER_ORDER,
            'commission_value' => '50.00',
            'status' => CampaignStatus::ACTIVE,
        ]);

        // Hold 800 SAR in escrow
        $holdTx = $this->ledgerService->reserveCampaignBudget(
            $campaign,
            '800.0000',
            Currency::SAR,
            $this->brandUser
        );

        $wallet->refresh();
        $this->assertEquals('1200.0000', $wallet->available_balance);
        $this->assertEquals('800.0000', $wallet->reserved_balance);
        $this->assertEquals('2000.0000', $wallet->total_balance);

        // Release 300 SAR back to available
        $releaseTx = $this->ledgerService->releaseCampaignBudget(
            $campaign,
            '300.0000',
            Currency::SAR,
            $this->brandUser,
            'فائض ميزانية بعد انتهاء فترة الترويج'
        );

        $wallet->refresh();
        $this->assertEquals('1500.0000', $wallet->available_balance);
        $this->assertEquals('500.0000', $wallet->reserved_balance);
        $this->assertEquals('2000.0000', $wallet->total_balance);
    }

    /**
     * 10. منع الأرصدة السالبة غير المسموح بها (InsufficientFundsException)
     */
    public function test_prevents_negative_wallet_balance_with_exception(): void
    {
        $wallet = $this->walletService->getOrCreateWallet($this->brandUser, Currency::SAR);
        $this->assertEquals('0.0000', $wallet->available_balance);

        $this->expectException(InsufficientFundsException::class);

        // Attempting to withdraw or hold 100 SAR when available balance is 0
        $this->ledgerService->postTransaction([
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::WITHDRAWAL,
            'idempotency_key' => 'overdraw-attempt',
            'description' => 'محاولة سحب تتجاوز الرصيد',
            'entries' => [
                ['account_code' => '2010_BRAND_WALLET_LIABILITY', 'wallet_id' => $wallet->id, 'entry_type' => LedgerEntryType::DEBIT, 'amount' => '100.0000'],
                ['account_code' => '1010_PLATFORM_CASH', 'entry_type' => LedgerEntryType::CREDIT, 'amount' => '100.0000'],
            ],
        ]);
    }

    /**
     * 11. منع المستخدم غير المصرح له من عرض المحافظ أو المالية
     */
    public function test_unauthorized_users_cannot_access_admin_finance_routes(): void
    {
        // 1. Guest user
        $response = $this->get(route('admin.finance.index'));
        $response->assertRedirect(route('login'));

        // 2. Brand user (not super admin)
        $response = $this->actingAs($this->brandUser)->get(route('admin.finance.index'));
        $response->assertForbidden();

        // 3. Marketer user (not super admin)
        $response = $this->actingAs($this->marketerUser)->get(route('admin.finance.index'));
        $response->assertForbidden();

        // 4. Super Admin user (Authorized)
        $response = $this->actingAs($this->superAdmin)->get(route('admin.finance.index'));
        $response->assertOk();
    }

    /**
     * 12. حماية بيانات العملاء المالية (Financial Data Privacy)
     */
    public function test_financial_views_require_super_admin_authentication(): void
    {
        $wallet = $this->walletService->getOrCreateWallet($this->brandUser, Currency::SAR);

        // Marketer cannot view brand wallet
        $response = $this->actingAs($this->marketerUser)->get(route('admin.finance.wallets.show', $wallet));
        $response->assertForbidden();

        // Super Admin can view brand wallet
        $response = $this->actingAs($this->superAdmin)->get(route('admin.finance.wallets.show', $wallet));
        $response->assertOk();
        $response->assertSee('شركة الرياض للتقنية');
    }

    /**
     * 13. تسجيل العمليات المالية في Audit Log
     */
    public function test_financial_actions_are_recorded_in_audit_log(): void
    {
        $wallet = $this->walletService->getOrCreateWallet($this->brandUser, Currency::AED);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::WALLET_CREATED->value,
            'entity_type' => Wallet::class,
            'entity_id' => $wallet->id,
        ]);

        $this->ledgerService->postTransaction([
            'currency' => Currency::AED,
            'transaction_type' => LedgerTransactionType::DEPOSIT,
            'idempotency_key' => 'audit-deposit-aed',
            'description' => 'إيداع تدقيقي بالدرهم',
            'created_by' => $this->superAdmin,
            'entries' => [
                ['account_code' => '1010_PLATFORM_CASH', 'entry_type' => LedgerEntryType::DEBIT, 'amount' => '400.0000'],
                ['account_code' => '2010_BRAND_WALLET_LIABILITY', 'wallet_id' => $wallet->id, 'entry_type' => LedgerEntryType::CREDIT, 'amount' => '400.0000'],
            ],
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::LEDGER_TRANSACTION_POSTED->value,
            'entity_type' => LedgerTransaction::class,
        ]);
    }

    /**
     * 14. التأكد من أن التحويلات القديمة لم تُرحّل تلقائيًا
     */
    public function test_legacy_conversions_are_not_automatically_posted_to_ledger(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandUser->brandProfile->id,
            'title' => 'حملة تحويلات سابقة',
            'destination_url' => 'https://example.com',
            'category' => 'تقنية',
            'description' => 'وصف الحملة التجريبية السابقة',
            'brief' => 'موجز الحملة التجريبية',
            'target_markets' => ['SA'],
            'currencies' => ['SAR'],
            'tracking_methods' => ['LINK'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '10.00',
            'status' => CampaignStatus::ACTIVE,
        ]);

        $conversion = Conversion::create([
            'campaign_id' => $campaign->id,
            'external_order_id' => 'ORD-HIST-001',
            'order_value' => '500.00',
            'currency' => 'SAR',
            'commission_value' => '50.00',
            'status' => ConversionStatus::APPROVED,
            'occurred_at' => now()->subDays(5),
            'verified_at' => now()->subDays(4),
        ]);

        // Verifies zero financial transactions exist for this conversion
        $this->assertEquals(0, LedgerTransaction::where('source_id', $conversion->id)->count());
        $this->assertEquals(0, LedgerTransaction::where('source_type', Conversion::class)->count());
    }

    /**
     * 15. التأكد من عدم استخدام float في العمليات المالية
     */
    public function test_no_float_precision_distortions_in_ledger(): void
    {
        // 0.1 + 0.2 = 0.3000 exactly
        $wallet = $this->walletService->getOrCreateWallet($this->brandUser, Currency::KWD);

        $tx = $this->ledgerService->postTransaction([
            'currency' => Currency::KWD,
            'transaction_type' => LedgerTransactionType::DEPOSIT,
            'idempotency_key' => 'kwd-fraction-test',
            'description' => 'إيداع بدقة 3 منازل عشرية للدينار الكويتي',
            'entries' => [
                ['account_code' => '1010_PLATFORM_CASH', 'entry_type' => LedgerEntryType::DEBIT, 'amount' => '0.3000'],
                ['account_code' => '2010_BRAND_WALLET_LIABILITY', 'wallet_id' => $wallet->id, 'entry_type' => LedgerEntryType::CREDIT, 'amount' => '0.3000'],
            ],
        ]);

        $wallet->refresh();
        $this->assertEquals('0.3000', $wallet->available_balance);
        $this->assertIsString($wallet->available_balance);
    }

    /**
     * 16. التأكد من عدم وجود أي اتصال ببوابة دفع حقيقية
     */
    public function test_no_external_payment_gateway_mock_or_live_call(): void
    {
        // Verify that the config has no live payment API keys or webhook listeners
        $this->assertNull(config('services.stripe.secret'));
        $this->assertNull(config('services.paypal.secret'));
        $this->assertNull(config('services.tap.secret'));
    }

    /**
     * 17. التحقق من سلامة ميزان المراجعة وتوازن دفتر الأستاذ (Trial Balance)
     */
    public function test_system_wide_trial_balance_invariant(): void
    {
        $tb = $this->ledgerService->getTrialBalance();

        foreach (Currency::values() as $curr) {
            $this->assertArrayHasKey($curr, $tb);
            $this->assertTrue($tb[$curr]['is_balanced']);
            $this->assertEquals('0.0000', $tb[$curr]['difference']);
        }
    }
}
