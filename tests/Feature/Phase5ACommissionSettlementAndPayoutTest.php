<?php

namespace Tests\Feature;

use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Enums\ConversionStatus;
use App\Enums\Currency;
use App\Enums\LedgerEntryType;
use App\Enums\LedgerTransactionType;
use App\Enums\PayoutRequestStatus;
use App\Enums\UserRole;
use App\Exceptions\InsufficientFundsException;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\CommissionSettlement;
use App\Models\Conversion;
use App\Models\MarketerProfile;
use App\Models\PayoutRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Ledger\CommissionSettlementService;
use App\Services\Ledger\LedgerService;
use App\Services\Ledger\PayoutService;
use App\Services\Ledger\WalletService;
use App\Services\Math\MoneyMath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5ACommissionSettlementAndPayoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $brandUser;
    protected User $marketerUser;
    protected BrandProfile $brandProfile;
    protected MarketerProfile $marketerProfile;
    protected Campaign $campaign;
    protected CampaignApplication $application;
    protected WalletService $walletService;
    protected LedgerService $ledgerService;
    protected CommissionSettlementService $settlementService;
    protected PayoutService $payoutService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->walletService = app(WalletService::class);
        $this->ledgerService = app(LedgerService::class);
        $this->settlementService = app(CommissionSettlementService::class);
        $this->payoutService = app(PayoutService::class);

        // Roles
        $superAdminRole = Role::firstOrCreate(['name' => UserRole::SUPER_ADMIN->value], ['label' => 'مدير النظام']);
        $brandRole = Role::firstOrCreate(['name' => UserRole::BRAND->value], ['label' => 'علامة تجارية']);
        $marketerRole = Role::firstOrCreate(['name' => UserRole::MARKETER->value], ['label' => 'مسوق']);

        // Users
        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@nomowly.com',
            'role' => UserRole::SUPER_ADMIN,
        ]);
        $this->superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        $this->brandUser = User::factory()->create([
            'email' => 'brand@nomowly.com',
            'role' => UserRole::BRAND,
        ]);
        $this->brandUser->roles()->syncWithoutDetaching([$brandRole->id]);
        $this->brandProfile = BrandProfile::create([
            'user_id' => $this->brandUser->id,
            'company_name' => 'متجر النخبة للتجارة',
            'website' => 'https://nokhba-store.com',
            'verified_at' => now(),
        ]);

        $this->marketerUser = User::factory()->create([
            'email' => 'marketer@nomowly.com',
            'role' => UserRole::MARKETER,
        ]);
        $this->marketerUser->roles()->syncWithoutDetaching([$marketerRole->id]);
        $this->marketerProfile = MarketerProfile::create([
            'user_id' => $this->marketerUser->id,
            'full_name' => 'أحمد المسوق المحترف',
            'country' => 'SA',
        ]);

        // Campaign & Application
        $this->campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfile->id,
            'title' => 'حملة العطور الفاخرة',
            'slug' => 'luxury-perfumes-campaign',
            'category' => 'عطور وتجميل',
            'category_slug' => 'perfumes',
            'brief' => 'موجز ترويجي لعطور فاخرة للشركاء',
            'description' => 'حملة ترويجية بنظام العمولة الثابتة على المبيعات',
            'status' => CampaignStatus::ACTIVE,
            'commission_type' => CampaignCommissionType::FIXED_PER_ORDER,
            'commission_value' => 50.00,
            'cookie_duration_days' => 30,
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'tracking_methods' => ['كود خصم حصري'],
        ]);

        $this->application = CampaignApplication::create([
            'campaign_id' => $this->campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'status' => \App\Enums\CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'PERFUME2026',
            'approved_at' => now(),
        ]);
    }

    /**
     * Helper to fund brand escrow for campaign
     */
    protected function fundBrandEscrow(string $amount = '500.0000', Currency $currency = Currency::SAR): Wallet
    {
        $wallet = $this->walletService->getOrCreateWallet($this->brandUser, $currency);

        // 1. Initial Deposit
        $this->ledgerService->postTransaction([
            'currency' => $currency,
            'transaction_type' => LedgerTransactionType::DEPOSIT,
            'idempotency_key' => 'dep-' . uniqid(),
            'description' => 'إيداع رصيد مالي بحساب العلامة التجارية',
            'source_type' => User::class,
            'source_id' => $this->brandUser->id,
            'created_by' => $this->superAdmin,
            'entries' => [
                [
                    'account_code' => '1010_PLATFORM_CASH',
                    'entry_type' => LedgerEntryType::DEBIT,
                    'amount' => $amount,
                    'description' => 'استلام إيداع بنكي من العميل',
                ],
                [
                    'account_code' => '2010_BRAND_WALLET_LIABILITY',
                    'wallet_id' => $wallet->id,
                    'entry_type' => LedgerEntryType::CREDIT,
                    'amount' => $amount,
                    'description' => 'قيد رصيد متاح في محفظة العميل',
                ],
            ],
        ]);

        // 2. Reserve Escrow for Campaign
        $this->ledgerService->reserveCampaignBudget($this->campaign, $amount, $currency, $this->brandUser);

        return $wallet->fresh();
    }

    public function test_approved_conversion_triggers_balanced_double_entry_settlement(): void
    {
        $this->fundBrandEscrow('1000.0000', Currency::SAR);

        $conversion = Conversion::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'external_order_id' => 'ORDER-101',
            'conversion_type' => 'SALE',
            'status' => ConversionStatus::PENDING,
            'order_value' => 500.00,
            'currency' => 'SAR',
            'commission_value' => 50.00,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->brandUser)
            ->post(route('brand.conversions.approve', $conversion->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $conversion->refresh();
        $this->assertEquals(ConversionStatus::APPROVED, $conversion->status);
        $this->assertTrue($conversion->isSettled());

        $settlement = $conversion->settlement;
        $this->assertNotNull($settlement);
        $this->assertEquals('50.0000', (string) $settlement->commission_amount);
        $this->assertEquals('SAR', $settlement->currency->value);

        // Marketer wallet available balance must have increased by 50.0000
        $marketerWallet = $this->walletService->getOrCreateWallet($this->marketerUser, Currency::SAR);
        $this->assertEquals('50.0000', (string) $marketerWallet->available_balance);

        // Brand wallet escrow (reserved_balance) must have decreased by 50.0000
        $brandWallet = $this->walletService->getOrCreateWallet($this->brandUser, Currency::SAR);
        $this->assertEquals('950.0000', (string) $brandWallet->reserved_balance);

        // Ledger transaction entries must be balanced
        $tx = $settlement->ledgerTransaction;
        $this->assertNotNull($tx);
        $this->assertTrue($tx->isBalanced());

        // Global trial balance must be 100% balanced
        $trial = $this->ledgerService->getTrialBalance(Currency::SAR);
        $this->assertTrue($trial['SAR']['is_balanced']);
        $this->assertEquals('0.0000', $trial['SAR']['difference']);
    }

    public function test_commission_settlement_is_strictly_idempotent(): void
    {
        $this->fundBrandEscrow('500.0000', Currency::SAR);

        $conversion = Conversion::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'external_order_id' => 'ORDER-IDEMP-01',
            'conversion_type' => 'SALE',
            'status' => ConversionStatus::APPROVED,
            'order_value' => 300.00,
            'currency' => 'SAR',
            'commission_value' => 50.00,
            'occurred_at' => now(),
        ]);

        // First settlement
        $settlement1 = $this->settlementService->settleConversion($conversion, $this->brandUser);
        $this->assertNotNull($settlement1);

        $initialMarketerBalance = $this->walletService->getOrCreateWallet($this->marketerUser, Currency::SAR)->available_balance;

        // Second duplicate call must return existing settlement and NOT double credit
        $settlement2 = $this->settlementService->settleConversion($conversion, $this->brandUser);
        $this->assertEquals($settlement1->id, $settlement2->id);

        $secondMarketerBalance = $this->walletService->getOrCreateWallet($this->marketerUser, Currency::SAR)->available_balance;
        $this->assertEquals((string) $initialMarketerBalance, (string) $secondMarketerBalance);

        // Commission settlement count for this conversion must still be exactly 1
        $this->assertEquals(1, CommissionSettlement::where('conversion_id', $conversion->id)->count());
    }

    public function test_conversion_approval_fails_gracefully_when_brand_escrow_is_insufficient(): void
    {
        // Brand has ZERO escrow reserved
        $conversion = Conversion::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'external_order_id' => 'ORDER-NO-ESCROW',
            'conversion_type' => 'SALE',
            'status' => ConversionStatus::PENDING,
            'order_value' => 200.00,
            'currency' => 'SAR',
            'commission_value' => 50.00,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->brandUser)
            ->post(route('brand.conversions.approve', $conversion->id));

        $response->assertRedirect();
        $response->assertSessionHas('warning');

        $conversion->refresh();
        $this->assertEquals(ConversionStatus::APPROVED, $conversion->status);
        $this->assertFalse($conversion->isSettled());

        // Marketer wallet must have zero
        $marketerWallet = $this->walletService->getOrCreateWallet($this->marketerUser, Currency::SAR);
        $this->assertEquals('0.0000', (string) $marketerWallet->available_balance);
    }

    public function test_conversion_reversal_creates_offsetting_ledger_transaction_and_claws_back_funds(): void
    {
        $this->fundBrandEscrow('500.0000', Currency::SAR);

        $conversion = Conversion::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'external_order_id' => 'ORDER-CANCEL-1',
            'conversion_type' => 'SALE',
            'status' => ConversionStatus::APPROVED,
            'order_value' => 400.00,
            'currency' => 'SAR',
            'commission_value' => 50.00,
            'occurred_at' => now(),
        ]);

        // Settle commission
        $this->settlementService->settleConversion($conversion, $this->brandUser);

        $marketerWallet = $this->walletService->getOrCreateWallet($this->marketerUser, Currency::SAR);
        $this->assertEquals('50.0000', (string) $marketerWallet->available_balance);

        // Now Brand cancels the conversion
        $response = $this->actingAs($this->brandUser)
            ->post(route('brand.conversions.cancel', $conversion->id), [
                'reason' => 'إلغاء الطلب واسترجاع المبلغ من قبل الزبون',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $conversion->refresh();
        $this->assertEquals(ConversionStatus::CANCELLED, $conversion->status);

        $settlement = $conversion->settlement->fresh();
        $this->assertTrue($settlement->isReversed());
        $this->assertNotNull($settlement->reversal_ledger_transaction_id);

        // Marketer available balance clawed back to 0.0000
        $marketerWallet->refresh();
        $this->assertEquals('0.0000', (string) $marketerWallet->available_balance);

        // Trial balance remains 100% balanced
        $trial = $this->ledgerService->getTrialBalance(Currency::SAR);
        $this->assertTrue($trial['SAR']['is_balanced']);
    }

    public function test_conversion_reversal_blocked_safely_if_marketer_available_balance_insufficient(): void
    {
        $this->fundBrandEscrow('500.0000', Currency::SAR);

        $conversion = Conversion::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'external_order_id' => 'ORDER-SPENT',
            'conversion_type' => 'SALE',
            'status' => ConversionStatus::APPROVED,
            'order_value' => 400.00,
            'currency' => 'SAR',
            'commission_value' => 50.00,
            'occurred_at' => now(),
        ]);

        $this->settlementService->settleConversion($conversion, $this->brandUser);

        // Marketer requested payout or available balance is zeroed
        $marketerWallet = $this->walletService->getOrCreateWallet($this->marketerUser, Currency::SAR);
        $marketerWallet->update(['available_balance' => '10.0000']); // less than 50.0000

        $this->expectException(InsufficientFundsException::class);
        $this->settlementService->reverseSettlement($conversion, $this->brandUser, 'طلب مرتجع');
    }

    public function test_marketer_can_view_wallet_and_request_payout(): void
    {
        // Give marketer 150 SAR available
        $marketerWallet = $this->walletService->getOrCreateWallet($this->marketerUser, Currency::SAR);
        $marketerWallet->update([
            'balance' => '150.0000',
            'available_balance' => '150.0000',
            'reserved_balance' => '0.0000',
        ]);

        // 1. Visit Wallet index
        $indexResponse = $this->actingAs($this->marketerUser)
            ->get(route('marketer.wallet.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('محفظتي وأرباح التسويق');
        $indexResponse->assertSee('150.00');

        // 2. Submit Payout Request
        $payoutResponse = $this->actingAs($this->marketerUser)
            ->post(route('marketer.wallet.payout-request'), [
                'wallet_id' => $marketerWallet->id,
                'amount' => 100.00,
                'bank_name' => 'مصرف الراجحي',
                'account_holder_name' => 'أحمد المسوق',
                'account_reference' => 'SA4480000123456789012345',
            ]);

        $payoutResponse->assertRedirect(route('marketer.wallet.index'));
        $payoutResponse->assertSessionHas('status');

        $payoutRequest = PayoutRequest::where('user_id', $this->marketerUser->id)->first();
        $this->assertNotNull($payoutRequest);
        $this->assertEquals(PayoutRequestStatus::REQUESTED, $payoutRequest->status);
        $this->assertEquals('100.0000', (string) $payoutRequest->amount);
        $this->assertEquals('SAR', $payoutRequest->currency->value);

        // Account reference must be masked for privacy (not full plaintext)
        $this->assertNotEquals('SA4480000123456789012345', $payoutRequest->account_reference);
        $this->assertStringContainsString('*', $payoutRequest->account_reference);

        // Wallet balances must reflect held funds:
        // Available balance: 150 - 100 = 50.0000
        // Reserved balance: 0 + 100 = 100.0000
        $marketerWallet->refresh();
        $this->assertEquals('50.0000', (string) $marketerWallet->available_balance);
        $this->assertEquals('100.0000', (string) $marketerWallet->reserved_balance);
    }

    public function test_marketer_cannot_request_payout_exceeding_available_balance(): void
    {
        $marketerWallet = $this->walletService->getOrCreateWallet($this->marketerUser, Currency::SAR);
        $marketerWallet->update([
            'balance' => '50.0000',
            'available_balance' => '50.0000',
            'reserved_balance' => '0.0000',
        ]);

        $response = $this->actingAs($this->marketerUser)
            ->post(route('marketer.wallet.payout-request'), [
                'wallet_id' => $marketerWallet->id,
                'amount' => 200.00, // exceeds 50.00
                'bank_name' => 'البنك الأهلي',
                'account_holder_name' => 'أحمد المسوق',
                'account_reference' => 'SA4410000123456789012345',
            ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals(0, PayoutRequest::count());

        $marketerWallet->refresh();
        $this->assertEquals('50.0000', (string) $marketerWallet->available_balance);
    }

    public function test_payout_self_approval_is_strictly_forbidden(): void
    {
        // Super Admin creates a payout request for their own wallet
        $adminWallet = $this->walletService->getOrCreateWallet($this->superAdmin, Currency::SAR);
        $adminWallet->update([
            'balance' => '200.0000',
            'available_balance' => '200.0000',
            'reserved_balance' => '0.0000',
        ]);

        $request = $this->payoutService->createPayoutRequest(
            $this->superAdmin,
            $adminWallet,
            '100.0000',
            ['bank_name' => 'بنك الرياض', 'account_reference' => 'SA1111222233334444555566']
        );

        // Attempting to self-approve via PayoutService must throw RuntimeException
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('غير مصرح لك باعتماد طلب سحب أرباح خاص بحسابك الشخصي.');

        $this->payoutService->approvePayoutRequest($request, $this->superAdmin);
    }

    public function test_admin_can_approve_payout_request(): void
    {
        $marketerWallet = $this->walletService->getOrCreateWallet($this->marketerUser, Currency::SAR);
        $marketerWallet->update([
            'balance' => '300.0000',
            'available_balance' => '300.0000',
            'reserved_balance' => '0.0000',
        ]);

        $payoutRequest = $this->payoutService->createPayoutRequest(
            $this->marketerUser,
            $marketerWallet,
            '200.0000',
            ['bank_name' => 'بنك الراجحي', 'account_reference' => 'SA4480000123456789012345']
        );

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.finance.payouts.approve', $payoutRequest->id));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $payoutRequest->refresh();
        $this->assertEquals(PayoutRequestStatus::APPROVED, $payoutRequest->status);
        $this->assertEquals($this->superAdmin->id, $payoutRequest->reviewed_by);
        $this->assertNotNull($payoutRequest->reviewed_at);
    }

    public function test_admin_can_reject_payout_request_and_funds_are_refunded_to_available_balance(): void
    {
        $marketerWallet = $this->walletService->getOrCreateWallet($this->marketerUser, Currency::SAR);
        $marketerWallet->update([
            'balance' => '200.0000',
            'available_balance' => '200.0000',
            'reserved_balance' => '0.0000',
        ]);

        $payoutRequest = $this->payoutService->createPayoutRequest(
            $this->marketerUser,
            $marketerWallet,
            '150.0000',
            ['bank_name' => 'بنك الراجحي', 'account_reference' => 'SA4480000123456789012345']
        );

        $marketerWallet->refresh();
        $this->assertEquals('50.0000', (string) $marketerWallet->available_balance);
        $this->assertEquals('150.0000', (string) $marketerWallet->reserved_balance);

        // Admin rejects the payout request
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.finance.payouts.reject', $payoutRequest->id), [
                'reason' => 'رقم الحساب غير مطابق لاسم المستفيد المعتمد',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $payoutRequest->refresh();
        $this->assertEquals(PayoutRequestStatus::REJECTED, $payoutRequest->status);
        $this->assertEquals('رقم الحساب غير مطابق لاسم المستفيد المعتمد', $payoutRequest->rejection_reason);

        // Held funds must be refunded to available balance
        $marketerWallet->refresh();
        $this->assertEquals('200.0000', (string) $marketerWallet->available_balance);
        $this->assertEquals('0.0000', (string) $marketerWallet->reserved_balance);
    }

    public function test_admin_can_mark_payout_as_paid_with_bank_reference_and_ledger_posting(): void
    {
        // First deposit platform cash so platform cash is positive
        $this->ledgerService->postTransaction([
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::DEPOSIT,
            'idempotency_key' => 'seed-cash-' . uniqid(),
            'description' => 'تغذية صندوق نقدية المنصة للتحويلات البنكية',
            'created_by' => $this->superAdmin,
            'entries' => [
                [
                    'account_code' => '1010_PLATFORM_CASH',
                    'entry_type' => LedgerEntryType::DEBIT,
                    'amount' => '1000.0000',
                    'description' => 'إيداع نقدي في الحساب البنكي التشغيلي',
                ],
                [
                    'account_code' => '3010_PLATFORM_EQUITY',
                    'entry_type' => LedgerEntryType::CREDIT,
                    'amount' => '1000.0000',
                    'description' => 'رأس مال تشغيلي للمنصة',
                ],
            ],
        ]);

        $marketerWallet = $this->walletService->getOrCreateWallet($this->marketerUser, Currency::SAR);
        $marketerWallet->update([
            'balance' => '250.0000',
            'available_balance' => '250.0000',
            'reserved_balance' => '0.0000',
        ]);

        $payoutRequest = $this->payoutService->createPayoutRequest(
            $this->marketerUser,
            $marketerWallet,
            '250.0000',
            ['bank_name' => 'البنك الأهلي السعودي', 'account_reference' => 'SA4410000123456789012345']
        );

        $this->payoutService->approvePayoutRequest($payoutRequest, $this->superAdmin);

        // Mark as paid with bank reference
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.finance.payouts.mark-paid', $payoutRequest->id), [
                'payment_reference' => 'TRX-BANK-SA-98231',
                'notes' => 'تم التحويل عبر سريـع بنجاح',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $payoutRequest->refresh();
        $this->assertEquals(PayoutRequestStatus::PAID, $payoutRequest->status);
        $this->assertEquals('TRX-BANK-SA-98231', $payoutRequest->payment_reference);
        $this->assertEquals('تم التحويل عبر سريـع بنجاح', $payoutRequest->admin_notes);
        $this->assertNotNull($payoutRequest->payout_ledger_transaction_id);

        // Marketer wallet reserved balance must be cleared
        $marketerWallet->refresh();
        $this->assertEquals('0.0000', (string) $marketerWallet->reserved_balance);
        $this->assertEquals('0.0000', (string) $marketerWallet->available_balance);
        $this->assertEquals('0.0000', (string) $marketerWallet->balance);

        // Ledger transaction must have posted debit to 2030 and credit to 1010
        $tx = $payoutRequest->payoutTransaction;
        $this->assertNotNull($tx);
        $this->assertTrue($tx->isBalanced());

        $debitEntry = $tx->entries->firstWhere('entry_type', LedgerEntryType::DEBIT);
        $this->assertEquals('2030_MARKETER_PAYABLE_LIABILITY', $debitEntry->account->code);
        $this->assertEquals('250.0000', (string) $debitEntry->amount);

        $creditEntry = $tx->entries->firstWhere('entry_type', LedgerEntryType::CREDIT);
        $this->assertEquals('1010_PLATFORM_CASH', $creditEntry->account->code);
        $this->assertEquals('250.0000', (string) $creditEntry->amount);

        // Invariant: Trial balance is balanced
        $trial = $this->ledgerService->getTrialBalance(Currency::SAR);
        $this->assertTrue($trial['SAR']['is_balanced']);
        $this->assertEquals('0.0000', $trial['SAR']['difference']);
    }

    public function test_admin_finance_payout_and_settlement_indexes_render_successfully(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.finance.payouts.index'));
        $response->assertOk();
        $response->assertSee('طلبات سحب الأرباح المصرفية');

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.finance.settlements.index'));
        $response->assertOk();
        $response->assertSee('سجل تسويات العمولات المحاسبية');
    }
}
