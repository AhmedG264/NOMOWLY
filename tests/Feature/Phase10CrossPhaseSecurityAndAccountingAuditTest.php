<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\BillingCycle;
use App\Enums\CampaignApplicationStatus;
use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Enums\ConversionStatus;
use App\Enums\Currency;
use App\Enums\LedgerEntryType;
use App\Enums\LedgerTransactionStatus;
use App\Enums\LedgerTransactionType;
use App\Enums\PaymentDepositStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentRefundStatus;
use App\Enums\PayoutRequestStatus;
use App\Enums\SubscriptionPlanStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WalletStatus;
use App\Enums\WalletType;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\UnbalancedLedgerTransactionException;
use App\Models\AttributionClick;
use App\Models\AuditLog;
use App\Models\BrandIntegration;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\CampaignTermsVersion;
use App\Models\CommissionSettlement;
use App\Models\Conversion;
use App\Models\FraudDetectionRule;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\MarketerProfile;
use App\Models\MarketerSubscription;
use App\Models\PaymentDeposit;
use App\Models\PaymentRefund;
use App\Models\PayoutRequest;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanPrice;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\AttributionService;
use App\Services\AuditService;
use App\Services\Campaign\CampaignTermsService;
use App\Services\Fraud\FraudDetectionService;
use App\Services\Ledger\CommissionSettlementService;
use App\Services\Ledger\LedgerService;
use App\Services\Ledger\PayoutService;
use App\Services\Ledger\WalletService;
use App\Services\Math\MoneyMath;
use App\Services\Payment\PaymentDepositService;
use App\Services\Subscription\SubscriptionEntitlementService;
use App\Services\Subscription\SubscriptionPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class Phase10CrossPhaseSecurityAndAccountingAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $brandA;
    protected User $brandB;
    protected User $marketerA;
    protected User $marketerB;
    protected BrandProfile $brandProfileA;
    protected BrandProfile $brandProfileB;
    protected MarketerProfile $marketerProfileA;
    protected MarketerProfile $marketerProfileB;

    protected LedgerService $ledgerService;
    protected WalletService $walletService;
    protected CommissionSettlementService $settlementService;
    protected PaymentDepositService $depositService;
    protected AttributionService $attributionService;
    protected FraudDetectionService $fraudService;
    protected SubscriptionPaymentService $subscriptionPaymentService;
    protected SubscriptionEntitlementService $entitlementService;
    protected CampaignTermsService $termsService;
    protected PayoutService $payoutService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');

        $this->ledgerService = app(LedgerService::class);
        $this->walletService = app(WalletService::class);
        $this->settlementService = app(CommissionSettlementService::class);
        $this->depositService = app(PaymentDepositService::class);
        $this->attributionService = app(AttributionService::class);
        $this->fraudService = app(FraudDetectionService::class);
        $this->subscriptionPaymentService = app(SubscriptionPaymentService::class);
        $this->entitlementService = app(SubscriptionEntitlementService::class);
        $this->termsService = app(CampaignTermsService::class);
        $this->payoutService = app(PayoutService::class);

        // 1. Super Admin
        $this->superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $saRole = Role::where('name', UserRole::SUPER_ADMIN->value)->first();
        if ($saRole) {
            $this->superAdmin->roles()->syncWithoutDetaching([$saRole->id => []]);
        }

        // 2. Brand A
        $this->brandA = User::factory()->create([
            'name' => 'Brand Owner Alpha',
            'email' => 'brand.a@example.com',
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $bRole = Role::where('name', UserRole::BRAND->value)->first();
        if ($bRole) {
            $this->brandA->roles()->syncWithoutDetaching([$bRole->id => []]);
        }
        $this->brandProfileA = BrandProfile::create([
            'user_id' => $this->brandA->id,
            'company_name' => 'Alpha Brand Retail Ltd',
            'currency' => 'SAR',
        ]);

        // 3. Brand B
        $this->brandB = User::factory()->create([
            'name' => 'Brand Owner Beta',
            'email' => 'brand.b@example.com',
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        if ($bRole) {
            $this->brandB->roles()->syncWithoutDetaching([$bRole->id => []]);
        }
        $this->brandProfileB = BrandProfile::create([
            'user_id' => $this->brandB->id,
            'company_name' => 'Beta Brand Stores Inc',
            'currency' => 'SAR',
        ]);

        // 4. Marketer A
        $this->marketerA = User::factory()->create([
            'name' => 'Top Marketer Ali',
            'email' => 'marketer.a@example.com',
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $mRole = Role::where('name', UserRole::MARKETER->value)->first();
        if ($mRole) {
            $this->marketerA->roles()->syncWithoutDetaching([$mRole->id => []]);
        }
        $this->marketerProfileA = MarketerProfile::create([
            'user_id' => $this->marketerA->id,
            'full_name' => 'Top Marketer Ali',
            'country' => 'SA',
        ]);

        // 5. Marketer B
        $this->marketerB = User::factory()->create([
            'name' => 'Marketer Bob',
            'email' => 'marketer.b@example.com',
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        if ($mRole) {
            $this->marketerB->roles()->syncWithoutDetaching([$mRole->id => []]);
        }
        $this->marketerProfileB = MarketerProfile::create([
            'user_id' => $this->marketerB->id,
            'full_name' => 'Creator Bob Media',
            'country' => 'SA',
        ]);
    }

    /**
     * 1. Multi-Tenant User Isolation: Brand cannot inspect or mutate another Brand's financial data
     */
    public function test_tenant_isolation_brand_cannot_access_or_mutate_other_brand_data(): void
    {
        // Brand B creates a campaign and conversion
        $campaignB = Campaign::create([
            'brand_profile_id' => $this->brandProfileB->id,
            'title' => 'Beta Campaign 2026',
            'slug' => 'beta-campaign-2026',
            'category' => 'Fashion',
            'category_slug' => 'fashion',
            'description' => 'Beta luxury wear campaign',
            'brief' => 'Full fashion brief',
            'target_markets' => ['SA'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '10.00',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['link'],
            'status' => CampaignStatus::ACTIVE,
            'starts_at' => now(),
        ]);
        $this->termsService->createInitialVersion($campaignB, $this->brandB);

        $conversionB = Conversion::create([
            'campaign_id' => $campaignB->id,
            'external_order_id' => 'ORD-BETA-909',
            'order_value' => '500.00',
            'currency' => 'SAR',
            'commission_value' => '50.00',
            'status' => ConversionStatus::PENDING,
            'occurred_at' => now(),
        ]);

        // Brand A attempts to view conversions: Beta conversion must NOT appear in Brand A list
        $response = $this->actingAs($this->brandA)->get(route('brand.conversions.index'));
        $response->assertStatus(200);
        $response->assertDontSee('ORD-BETA-909');

        // Brand A attempts to directly POST approve on Brand B's conversion -> Must be forbidden (403)
        $approveRes = $this->actingAs($this->brandA)->post(route('brand.conversions.approve', $conversionB));
        $approveRes->assertStatus(403);
        $this->assertEquals(ConversionStatus::PENDING, $conversionB->fresh()->status);

        // Brand A attempts to directly POST reject on Brand B's conversion -> 403
        $rejectRes = $this->actingAs($this->brandA)->post(route('brand.conversions.reject', $conversionB), [
            'reason' => 'Unauthorized rejection attempt',
        ]);
        $rejectRes->assertStatus(403);
        $this->assertEquals(ConversionStatus::PENDING, $conversionB->fresh()->status);

        // Brand A attempts to initiate or callback a deposit for Brand B -> Brand A cannot view or confirm
        $depositB = PaymentDeposit::create([
            'deposit_number' => 'DEP-BETA-123',
            'user_id' => $this->brandB->id,
            'wallet_id' => $this->walletService->getOrCreateWallet($this->brandB, Currency::SAR)->id,
            'currency' => Currency::SAR,
            'amount' => '1000.0000',
            'fee_amount' => '0.0000',
            'net_amount' => '1000.0000',
            'provider' => PaymentProvider::FAKE,
            'status' => PaymentDepositStatus::PENDING,
            'idempotency_key' => 'DEP_BETA_123',
        ]);

        $callbackRes = $this->actingAs($this->brandA)->get(route('brand.wallet.deposit.callback', ['deposit_ref' => 'DEP-BETA-123']));
        // SUT redirects with error "لم يتم العثور على سجل الإيداع المطلوب"
        $callbackRes->assertSessionHas('error');
        $this->assertEquals(PaymentDepositStatus::PENDING, $depositB->fresh()->status);
    }

    /**
     * 2. Multi-Tenant User Isolation: Marketer cannot access or mutate another Marketer's wallets or payouts
     */
    public function test_tenant_isolation_marketer_cannot_access_or_mutate_other_marketer_data(): void
    {
        $walletB = $this->walletService->getOrCreateWallet($this->marketerB, Currency::SAR);
        $walletB->update(['available_balance' => '500.0000']);

        $payoutB = $this->payoutService->createPayoutRequest($this->marketerB, $walletB, '200.0000', [
            'bank_name' => 'Alinma Bank',
            'account_holder_name' => 'Bob Marketer',
            'account_reference' => 'SA4400001234567890123456',
        ]);

        // Marketer A visits wallet hub: Bob's payout request must NOT appear in Ali's page
        $res = $this->actingAs($this->marketerA)->get(route('marketer.wallet.index'));
        $res->assertStatus(200);
        $res->assertDontSee($payoutB->request_number);

        // Marketer A attempts to submit a payout request by sending Marketer B's wallet UUID -> Must be rejected (404/403)
        $maliciousRes = $this->actingAs($this->marketerA)->post(route('marketer.wallet.payout-request'), [
            'wallet_id' => $walletB->id,
            'amount' => '100.00',
            'bank_name' => 'Fake Bank',
            'account_holder_name' => 'Ali Hacker',
            'account_reference' => 'SA0000000000000000000000',
        ]);
        $maliciousRes->assertStatus(404); // wallet_id scoped strictly to $user->id
    }

    /**
     * 3. RBAC & Security: Regular users cannot access Super Admin routes or elevate privileges
     */
    public function test_unauthorized_users_strictly_blocked_from_admin_and_financial_routes(): void
    {
        // Marketer attempts admin routes
        $this->actingAs($this->marketerA)->get(route('admin.finance.index'))->assertStatus(403);
        $this->actingAs($this->marketerA)->get(route('admin.conversions.index'))->assertStatus(403);
        $this->actingAs($this->marketerA)->get(route('admin.roles.index'))->assertStatus(403);
        $this->actingAs($this->marketerA)->get(route('admin.audit-logs.index'))->assertStatus(403);

        // Brand attempts admin routes
        $this->actingAs($this->brandA)->get(route('admin.finance.wallets'))->assertStatus(403);
        $this->actingAs($this->brandA)->get(route('admin.conversions.rules'))->assertStatus(403);

        // Guest redirected to login
        auth()->logout();
        $this->get(route('admin.finance.index'))->assertRedirect(route('login'));
    }

    /**
     * 4. Double-Entry Accounting Invariant: Every posted transaction has Debit == Credit
     */
    public function test_double_entry_ledger_invariants_and_exact_balancing(): void
    {
        $wallet = $this->walletService->getOrCreateWallet($this->brandA, Currency::SAR);

        // 1. Balanced transaction succeeds
        $tx = $this->ledgerService->postTransaction([
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::DEPOSIT,
            'idempotency_key' => 'TX_BALANCED_AUDIT_01',
            'description' => 'Balanced test deposit',
            'created_by' => $this->brandA,
            'entries' => [
                [
                    'account_code' => '1010_PLATFORM_CASH',
                    'entry_type' => LedgerEntryType::DEBIT,
                    'amount' => '150.0000',
                    'description' => 'Dr platform cash',
                ],
                [
                    'account_code' => '2010_BRAND_WALLET_LIABILITY',
                    'wallet_id' => $wallet->id,
                    'entry_type' => LedgerEntryType::CREDIT,
                    'amount' => '150.0000',
                    'description' => 'Cr brand wallet liability',
                ],
            ],
        ]);

        $this->assertTrue($tx->isBalanced());
        $this->assertEquals(LedgerTransactionStatus::POSTED, $tx->status);

        // 2. Unbalanced transaction throws UnbalancedLedgerTransactionException
        $this->expectException(UnbalancedLedgerTransactionException::class);
        $this->ledgerService->postTransaction([
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::DEPOSIT,
            'idempotency_key' => 'TX_UNBALANCED_AUDIT_02',
            'description' => 'Unbalanced test deposit',
            'created_by' => $this->brandA,
            'entries' => [
                [
                    'account_code' => '1010_PLATFORM_CASH',
                    'entry_type' => LedgerEntryType::DEBIT,
                    'amount' => '100.0000',
                ],
                [
                    'account_code' => '2010_BRAND_WALLET_LIABILITY',
                    'wallet_id' => $wallet->id,
                    'entry_type' => LedgerEntryType::CREDIT,
                    'amount' => '99.9900', // Unbalanced!
                ],
            ],
        ]);
    }

    /**
     * 5. Immutability Protection: Posted Ledger Entries and Transactions cannot be deleted or mutated
     */
    public function test_ledger_entries_and_transactions_are_strictly_immutable(): void
    {
        $wallet = $this->walletService->getOrCreateWallet($this->brandA, Currency::SAR);

        $tx = $this->ledgerService->postTransaction([
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::DEPOSIT,
            'idempotency_key' => 'TX_IMMUTABLE_TEST_01',
            'description' => 'Immutable audit transaction',
            'created_by' => $this->brandA,
            'entries' => [
                [
                    'account_code' => '1010_PLATFORM_CASH',
                    'entry_type' => LedgerEntryType::DEBIT,
                    'amount' => '200.0000',
                ],
                [
                    'account_code' => '2010_BRAND_WALLET_LIABILITY',
                    'wallet_id' => $wallet->id,
                    'entry_type' => LedgerEntryType::CREDIT,
                    'amount' => '200.0000',
                ],
            ],
        ]);

        $entry = $tx->entries->first();

        // 1. Attempt to update ledger entry -> RuntimeException
        try {
            $entry->update(['amount' => '300.0000']);
            $this->fail('Expected RuntimeException when updating LedgerEntry');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('غير قابلة للتعديل', $e->getMessage());
        }

        // 2. Attempt to delete ledger entry -> RuntimeException
        try {
            $entry->delete();
            $this->fail('Expected RuntimeException when deleting LedgerEntry');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('غير قابلة للحذف', $e->getMessage());
        }

        // 3. Attempt to delete ledger transaction -> RuntimeException
        try {
            $tx->delete();
            $this->fail('Expected RuntimeException when deleting LedgerTransaction');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('غير قابلة للحذف', $e->getMessage());
        }

        // 4. Attempt to mutate core fields on LedgerTransaction -> RuntimeException
        try {
            $tx->update(['currency' => Currency::USD]);
            $this->fail('Expected RuntimeException when modifying currency on LedgerTransaction');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('غير قابل للتعديل', $e->getMessage());
        }
    }

    /**
     * 6. Concurrency & Idempotency: Deposit confirmation, conversion recording, and settlement deduplication
     */
    public function test_concurrency_and_idempotency_guards(): void
    {
        $brandWallet = $this->walletService->getOrCreateWallet($this->brandA, Currency::SAR);
        $marketerWallet = $this->walletService->getOrCreateWallet($this->marketerA, Currency::SAR);

        // A. Deposit Idempotency
        $deposit = PaymentDeposit::create([
            'deposit_number' => 'DEP-AUDIT-IDEMP-01',
            'user_id' => $this->brandA->id,
            'wallet_id' => $brandWallet->id,
            'currency' => Currency::SAR,
            'amount' => '1000.0000',
            'fee_amount' => '0.0000',
            'net_amount' => '1000.0000',
            'provider' => PaymentProvider::FAKE,
            'status' => PaymentDepositStatus::PENDING,
            'idempotency_key' => 'DEP_AUDIT_IDEMP_01',
        ]);

        // First confirmation
        $this->depositService->confirmDeposit($deposit, 'tx_fake_001');
        $this->assertEquals('1000.0000', $brandWallet->fresh()->available_balance);

        // Second confirmation (replayed webhook or race) -> MUST return existing without crediting again
        $this->depositService->confirmDeposit($deposit->fresh(), 'tx_fake_001');
        $this->assertEquals('1000.0000', $brandWallet->fresh()->available_balance);

        // B. Campaign Escrow & Conversion Setup
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfileA->id,
            'title' => 'Alpha Audit Campaign',
            'slug' => 'alpha-audit-campaign',
            'category' => 'Retail',
            'category_slug' => 'retail',
            'description' => 'Test campaign',
            'brief' => 'Brief',
            'target_markets' => ['SA'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::FIXED_PER_ORDER,
            'commission_value' => '50.00',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['link'],
            'status' => CampaignStatus::ACTIVE,
            'starts_at' => now(),
        ]);
        $this->termsService->createInitialVersion($campaign, $this->brandA);

        // Marketer applies and is approved
        $application = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfileA->id,
            'terms_version_id' => $campaign->current_terms_version_id,
            'tracking_code' => 'ALIAUDIT',
            'status' => CampaignApplicationStatus::APPROVED,
            'applied_at' => now(),
        ]);

        // Brand reserves 500 SAR into escrow
        $this->ledgerService->reserveCampaignBudget($campaign, '500.0000', Currency::SAR, $this->brandA);
        $this->assertEquals('500.0000', $brandWallet->fresh()->available_balance);
        $this->assertEquals('500.0000', $brandWallet->fresh()->reserved_balance);

        // Click and Conversion Recording
        $click = AttributionClick::create([
            'campaign_id' => $campaign->id,
            'campaign_application_id' => $application->id,
            'terms_version_id' => $campaign->current_terms_version_id,
            'tracking_code' => 'ALIAUDIT',
            'visitor_id' => 'vid_audit_123',
            'session_id' => 'sid_audit_123',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent_hash' => hash('sha256', 'Mozilla'),
            'device_type' => 'desktop',
            'created_at' => now(),
        ]);

        $conv1 = $this->attributionService->recordConversion([
            'campaign' => $campaign,
            'external_order_id' => 'ORD-IDEMP-001',
            'order_value' => '250.00',
            'currency' => 'SAR',
            'tracking_code' => 'ALIAUDIT',
        ]);

        // Duplicate conversion recording with same external_order_id -> MUST return existing conversion
        $conv2 = $this->attributionService->recordConversion([
            'campaign' => $campaign,
            'external_order_id' => 'ORD-IDEMP-001',
            'order_value' => '250.00',
            'currency' => 'SAR',
            'tracking_code' => 'ALIAUDIT',
        ]);
        $this->assertEquals($conv1->id, $conv2->id);
        $this->assertEquals(1, Conversion::where('campaign_id', $campaign->id)->where('external_order_id', 'ORD-IDEMP-001')->count());

        // Approve and settle conversion
        $conv1->update(['status' => ConversionStatus::APPROVED, 'verified_at' => now()]);
        $settlement = $this->settlementService->settleConversion($conv1, $this->superAdmin);
        $this->assertNotNull($settlement);
        $this->assertEquals('50.0000', $marketerWallet->fresh()->available_balance);
        $this->assertEquals('450.0000', $brandWallet->fresh()->reserved_balance);

        // Re-settling the same conversion -> MUST be idempotent and not credit marketer twice
        $settlement2 = $this->settlementService->settleConversion($conv1, $this->superAdmin);
        $this->assertEquals($settlement->id, $settlement2->id);
        $this->assertEquals('50.0000', $marketerWallet->fresh()->available_balance);
    }

    /**
     * 7. Accounting Reconciliation: Wallet balance matches transaction statement balances
     */
    public function test_wallet_reconciliation_detects_consistency(): void
    {
        $wallet = $this->walletService->getOrCreateWallet($this->brandA, Currency::SAR);

        // Initially zero and matched
        $reconciliation = $this->walletService->reconcileWallet($wallet);
        $this->assertTrue($reconciliation['is_matched']);
        $this->assertEquals('0.0000', $reconciliation['cached_total']);

        // Post a deposit
        $deposit = PaymentDeposit::create([
            'deposit_number' => 'DEP-RECON-01',
            'user_id' => $this->brandA->id,
            'wallet_id' => $wallet->id,
            'currency' => Currency::SAR,
            'amount' => '300.0000',
            'fee_amount' => '0.0000',
            'net_amount' => '300.0000',
            'provider' => PaymentProvider::FAKE,
            'status' => PaymentDepositStatus::PENDING,
            'idempotency_key' => 'DEP_RECON_01',
        ]);
        $this->depositService->confirmDeposit($deposit);

        $reconciliationAfter = $this->walletService->reconcileWallet($wallet->fresh());
        $this->assertTrue($reconciliationAfter['is_matched']);
        $this->assertEquals('300.0000', $reconciliationAfter['cached_available']);
        $this->assertEquals('300.0000', $reconciliationAfter['statement_available']);
    }

    /**
     * 8. Refund Integrity: Cannot refund more than deposit amount or available balance
     */
    public function test_refund_lifecycle_and_escrow_protection(): void
    {
        $wallet = $this->walletService->getOrCreateWallet($this->brandA, Currency::SAR);

        $deposit = PaymentDeposit::create([
            'deposit_number' => 'DEP-REFUND-AUDIT',
            'user_id' => $this->brandA->id,
            'wallet_id' => $wallet->id,
            'currency' => Currency::SAR,
            'amount' => '500.0000',
            'fee_amount' => '0.0000',
            'net_amount' => '500.0000',
            'provider' => PaymentProvider::FAKE,
            'status' => PaymentDepositStatus::PENDING,
            'idempotency_key' => 'DEP_REFUND_AUDIT',
        ]);
        $this->depositService->confirmDeposit($deposit);

        // 1. Partial refund of 200 SAR
        $refund = $this->depositService->processRefund([
            'deposit' => $deposit->fresh(),
            'amount' => '200.0000',
            'reason' => 'Customer requested partial refund',
            'performer' => $this->superAdmin,
        ]);
        $this->assertEquals(PaymentRefundStatus::SUCCEEDED, $refund->status);
        $this->assertEquals('300.0000', $wallet->fresh()->available_balance);
        $this->assertEquals(PaymentDepositStatus::PARTIALLY_REFUNDED, $deposit->fresh()->status);

        // 2. Attempting to refund 350 SAR (remaining refundable is 300) -> InvalidArgumentException
        try {
            $this->depositService->processRefund([
                'deposit' => $deposit->fresh(),
                'amount' => '350.0000',
                'reason' => 'Excessive refund attempt',
                'performer' => $this->superAdmin,
            ]);
            $this->fail('Expected InvalidArgumentException for excessive refund amount');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('يتجاوز الرصيد القابل للاسترداد', $e->getMessage());
        }

        // 3. Complete remaining refund of 300 SAR
        $refund2 = $this->depositService->processRefund([
            'deposit' => $deposit->fresh(),
            'amount' => '300.0000',
            'reason' => 'Final refund',
            'performer' => $this->superAdmin,
        ]);
        $this->assertEquals(PaymentDepositStatus::REFUNDED, $deposit->fresh()->status);
        $this->assertEquals('0.0000', $wallet->fresh()->available_balance);
    }

    /**
     * 9. Contractual Locking: Campaign terms lock on activity and cannot be modified directly
     */
    public function test_campaign_terms_contractual_lock_and_snapshot_immutability(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfileA->id,
            'title' => 'Lock Test Campaign',
            'slug' => 'lock-test-campaign',
            'category' => 'Tech',
            'category_slug' => 'tech',
            'description' => 'Contract lock campaign',
            'brief' => 'Brief',
            'target_markets' => ['SA'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15.00',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['link'],
            'status' => CampaignStatus::ACTIVE,
            'starts_at' => now(),
        ]);
        $v1 = $this->termsService->createInitialVersion($campaign, $this->brandA);

        $this->assertFalse($campaign->isTermsLocked());

        // Approve an applicant -> automatically locks campaign terms
        $application = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfileA->id,
            'terms_version_id' => $v1->id,
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'LOCKEDMARKETER',
            'applied_at' => now(),
        ]);
        $campaign->lockTerms('Lock on approval');

        $this->assertTrue($campaign->fresh()->isTermsLocked());

        // Attempting to directly update commission rate throws ValidationException
        try {
            $this->termsService->updateUnlockedTerms($campaign->fresh(), [
                'commission_value' => '20.00',
            ], $this->brandA);
            $this->fail('Expected ValidationException when updating locked terms');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }

        // Creating and approving a change request creates v2 without mutating v1
        $request = $this->termsService->createChangeRequest(
            $campaign->fresh(),
            ['commission_value' => '20.00'],
            'Seasonal holiday promo adjustment',
            'NEW_MARKETERS_ONLY',
            null,
            $this->brandA
        );

        $v2 = $this->termsService->approveChangeRequest($request, $this->superAdmin, 'Approved by admin');
        $this->assertEquals(2, $v2->version_number);
        $this->assertEquals('20.00', $v2->commission_value);
        $this->assertEquals('15.00', $v1->fresh()->commission_value); // Historical v1 remains untouched
    }

    /**
     * 10. Fraud Detection & Investigation: Suspicious conversions are flagged, investigable, and audited
     */
    public function test_fraud_detection_and_investigation_workflow(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfileA->id,
            'title' => 'Fraud Test Campaign',
            'slug' => 'fraud-test-campaign',
            'category' => 'Electronics',
            'category_slug' => 'electronics',
            'description' => 'Fraud detection campaign',
            'brief' => 'Brief',
            'target_markets' => ['SA'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '10.00',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['link'],
            'status' => CampaignStatus::ACTIVE,
            'starts_at' => now(),
        ]);
        $this->termsService->createInitialVersion($campaign, $this->brandA);

        $application = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfileA->id,
            'terms_version_id' => $campaign->current_terms_version_id,
            'tracking_code' => 'FRAUDCHECK',
            'status' => CampaignApplicationStatus::APPROVED,
            'applied_at' => now(),
        ]);

        // Rapid conversion: Click occurred only 1 second ago (threshold is 3 seconds)
        $click = AttributionClick::create([
            'campaign_id' => $campaign->id,
            'campaign_application_id' => $application->id,
            'terms_version_id' => $campaign->current_terms_version_id,
            'tracking_code' => 'FRAUDCHECK',
            'visitor_id' => 'vid_rapid_999',
            'session_id' => 'sid_rapid_999',
            'ip_hash' => hash('sha256', '192.168.1.1'),
            'user_agent_hash' => hash('sha256', 'BotUA'),
            'device_type' => 'desktop',
            'created_at' => now()->subSeconds(1),
        ]);

        $conversion = $this->attributionService->recordConversion([
            'campaign' => $campaign,
            'external_order_id' => 'ORD-FRAUD-001',
            'order_value' => '100.00',
            'currency' => 'SAR',
            'visitor_id' => 'vid_rapid_999',
            'tracking_code' => 'FRAUDCHECK',
        ]);

        $this->assertTrue($conversion->is_suspicious);
        $this->assertGreaterThan(0, $conversion->fraud_risk_score);

        // Admin investigates: clears suspicion with notes
        $clearRes = $this->actingAs($this->superAdmin)->post(route('admin.conversions.investigate', $conversion), [
            'decision' => 'CLEAR_SUSPICION',
            'notes' => 'Customer verified and confirmed payment via trusted bank 3DS.',
        ]);
        $clearRes->assertSessionHas('success');
        $this->assertFalse($conversion->fresh()->is_suspicious);
        $this->assertEquals($this->superAdmin->id, $conversion->fresh()->investigated_by_user_id);
    }
}
