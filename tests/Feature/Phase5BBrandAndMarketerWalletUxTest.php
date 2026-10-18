<?php

namespace Tests\Feature;

use App\Enums\ConversionStatus;
use App\Enums\Currency;
use App\Enums\LedgerEntryType;
use App\Enums\LedgerTransactionStatus;
use App\Enums\LedgerTransactionType;
use App\Enums\PayoutRequestStatus;
use App\Enums\UserRole;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\CommissionSettlement;
use App\Models\Conversion;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\MarketerProfile;
use App\Models\PayoutRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Ledger\CommissionSettlementService;
use App\Services\Ledger\LedgerService;
use App\Services\Ledger\PayoutService;
use App\Services\Ledger\WalletService;
use App\Services\Math\MoneyMath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5BBrandAndMarketerWalletUxTest extends TestCase
{
    use RefreshDatabase;

    protected User $brandUserA;
    protected BrandProfile $brandProfileA;

    protected User $brandUserB;
    protected BrandProfile $brandProfileB;

    protected User $marketerUserA;
    protected MarketerProfile $marketerProfileA;

    protected User $marketerUserB;
    protected MarketerProfile $marketerProfileB;

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
        $brandRole = Role::firstOrCreate(['name' => UserRole::BRAND->value], ['label' => 'علامة تجارية']);
        $marketerRole = Role::firstOrCreate(['name' => UserRole::MARKETER->value], ['label' => 'مسوق']);

        // 1. Setup Brand A
        $this->brandUserA = User::factory()->create([
            'email' => 'brand-a@nomowly.com',
            'role' => UserRole::BRAND,
        ]);
        $this->brandUserA->roles()->syncWithoutDetaching([$brandRole->id]);
        $this->brandProfileA = BrandProfile::create([
            'user_id' => $this->brandUserA->id,
            'company_name' => 'متجر الرياض التقني (Brand A)',
            'website' => 'https://brand-a.example.com',
            'currency' => 'SAR',
        ]);

        // 2. Setup Brand B
        $this->brandUserB = User::factory()->create([
            'email' => 'brand-b@nomowly.com',
            'role' => UserRole::BRAND,
        ]);
        $this->brandUserB->roles()->syncWithoutDetaching([$brandRole->id]);
        $this->brandProfileB = BrandProfile::create([
            'user_id' => $this->brandUserB->id,
            'company_name' => 'متجر النيل للإلكترونيات (Brand B)',
            'website' => 'https://brand-b.example.com',
            'currency' => 'EGP',
        ]);

        // 3. Setup Marketer A
        $this->marketerUserA = User::factory()->create([
            'email' => 'marketer-a@nomowly.com',
            'role' => UserRole::MARKETER,
        ]);
        $this->marketerUserA->roles()->syncWithoutDetaching([$marketerRole->id]);
        $this->marketerProfileA = MarketerProfile::create([
            'user_id' => $this->marketerUserA->id,
            'full_name' => 'سارة المسوقة (Marketer A)',
            'bio' => 'مؤثرة تقنية في التسويق بالأداء',
        ]);

        // 4. Setup Marketer B
        $this->marketerUserB = User::factory()->create([
            'email' => 'marketer-b@nomowly.com',
            'role' => UserRole::MARKETER,
        ]);
        $this->marketerUserB->roles()->syncWithoutDetaching([$marketerRole->id]);
        $this->marketerProfileB = MarketerProfile::create([
            'user_id' => $this->marketerUserB->id,
            'full_name' => 'خالد المسوق (Marketer B)',
            'bio' => 'مسوق إعلانات ومحتوى',
        ]);

        // Run Chart of accounts seeder if accounts don't exist
        if (\App\Models\LedgerAccount::count() === 0) {
            $seeder = new \Database\Seeders\ChartOfAccountsSeeder();
            $seeder->run();
        }
    }

    public function test_brand_can_view_own_wallet_with_accurate_currency_isolation(): void
    {
        // Setup Brand A wallets
        $walletSAR = $this->walletService->getOrCreateWallet($this->brandUserA, Currency::SAR);
        $walletUSD = $this->walletService->getOrCreateWallet($this->brandUserA, Currency::USD);

        $walletSAR->update([
            'balance' => '500.0000',
            'available_balance' => '300.0000',
            'reserved_balance' => '200.0000',
        ]);

        $walletUSD->update([
            'balance' => '100.0000',
            'available_balance' => '100.0000',
            'reserved_balance' => '0.0000',
        ]);

        // GET brand wallet page
        $response = $this->actingAs($this->brandUserA)->get(route('brand.wallet.index', ['currency' => 'SAR']));

        $response->assertOk();
        $response->assertSee('محفظة المتجر وإدارة ميزانيات الحملات');
        $response->assertSee('متجر الرياض التقني (Brand A)');
        $response->assertSee('SAR');
        // Assert currency balances are isolated and displayed accurately
        $response->assertSee('300.00'); // available SAR
        $response->assertSee('200.00'); // reserved SAR
        $response->assertSee('500.00'); // total SAR
        $response->assertSee('USD');
        $response->assertSee('100.00'); // available USD
        $response->assertDontSee('400.00 USD'); // Not combined!

        // Assert viewing wallet is purely idempotent and did NOT create transactions
        $this->assertEquals(0, LedgerTransaction::where('created_by', $this->brandUserA->id)->count());
    }

    public function test_brand_cannot_access_or_view_other_brands_wallets_or_movements(): void
    {
        // Brand B has a wallet and transactions
        $walletB = $this->walletService->getOrCreateWallet($this->brandUserB, Currency::EGP);
        $walletB->update([
            'balance' => '9999.0000',
            'available_balance' => '9999.0000',
            'reserved_balance' => '0.0000',
        ]);

        // Brand A views wallet page
        $response = $this->actingAs($this->brandUserA)->get(route('brand.wallet.index', ['currency' => 'EGP']));

        $response->assertOk();
        // Brand A should see their OWN EGP wallet (which has 0.00), NOT Brand B's 9999.00
        $response->assertDontSee('متجر النيل للإلكترونيات (Brand B)');
        $response->assertDontSee('9999.00');
    }

    public function test_brand_statement_filters_by_type_status_dates_and_reference(): void
    {
        $walletSAR = $this->walletService->getOrCreateWallet($this->brandUserA, Currency::SAR);
        $walletSAR->update([
            'balance' => '1000.0000',
            'available_balance' => '1000.0000',
            'reserved_balance' => '0.0000',
        ]);

        // Create Campaign for Brand A
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfileA->id,
            'title' => 'حملة العطور الخاصة',
            'slug' => 'perfumes-special-' . uniqid(),
            'category' => 'عطور',
            'category_slug' => 'perfumes',
            'brief' => 'موجز ترويجي لعطور فاخرة للشركاء',
            'description' => 'حملة ترويجية بنظام العمولة الثابتة',
            'commission_type' => \App\Enums\CampaignCommissionType::FIXED_PER_ORDER,
            'commission_value' => '50.00',
            'currency' => 'SAR',
            'cookie_duration_days' => 30,
            'status' => \App\Enums\CampaignStatus::ACTIVE,
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'tracking_methods' => ['كود خصم حصري'],
        ]);

        // Reserve budget for campaign
        $holdTx = $this->ledgerService->reserveCampaignBudget(
            $campaign,
            '400.00',
            Currency::SAR,
            $this->brandUserA,
            'idemp-brand-hold-1'
        );

        $this->assertNotNull($holdTx);

        // 1. Query with currency SAR
        $respSAR = $this->actingAs($this->brandUserA)->get(route('brand.wallet.index', [
            'currency' => 'SAR',
            'transaction_type' => LedgerTransactionType::ESCROW_HOLD->value,
        ]));
        $respSAR->assertOk();
        $respSAR->assertSee($holdTx->transaction_number);
        $respSAR->assertSee('حملة العطور الخاصة');
        $respSAR->assertSee('400.00');

        // 2. Query with non-matching filter (e.g. DEPOSIT) should not show the ESCROW_HOLD tx
        $respFilter = $this->actingAs($this->brandUserA)->get(route('brand.wallet.index', [
            'currency' => 'SAR',
            'transaction_type' => LedgerTransactionType::DEPOSIT->value,
        ]));
        $respFilter->assertOk();
        $respFilter->assertDontSee($holdTx->transaction_number);

        // 3. Query with status filter POSTED
        $respStatus = $this->actingAs($this->brandUserA)->get(route('brand.wallet.index', [
            'currency' => 'SAR',
            'status' => LedgerTransactionStatus::POSTED->value,
        ]));
        $respStatus->assertOk();
        $respStatus->assertSee($holdTx->transaction_number);
    }

    public function test_marketer_can_view_own_wallet_with_real_metrics_and_earnings_breakdown(): void
    {
        // Setup Marketer A wallet
        $walletSAR = $this->walletService->getOrCreateWallet($this->marketerUserA, Currency::SAR);
        $walletSAR->update([
            'balance' => '350.0000',
            'available_balance' => '300.0000',
            'reserved_balance' => '50.0000', // e.g. 50 held in requested payout
        ]);

        // Campaign and Application
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfileA->id,
            'title' => 'حملة الأزياء الصيفية',
            'slug' => 'summer-fashion-' . uniqid(),
            'category' => 'أزياء',
            'category_slug' => 'fashion',
            'brief' => 'موجز ترويجي لأزياء صيفية',
            'description' => 'حملة أزياء صيفية',
            'commission_type' => \App\Enums\CampaignCommissionType::FIXED_PER_ORDER,
            'commission_value' => '75.00',
            'currency' => 'SAR',
            'cookie_duration_days' => 30,
            'status' => \App\Enums\CampaignStatus::ACTIVE,
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'tracking_methods' => ['كود خصم حصري'],
        ]);

        $appA = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfileA->id,
            'status' => \App\Enums\CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'SARA-SUMMER-77',
            'approved_at' => now(),
        ]);

        // Conversions: 1 approved (75), 1 pending (75)
        $approvedConv = Conversion::create([
            'campaign_id' => $campaign->id,
            'campaign_application_id' => $appA->id,
            'external_order_id' => 'ORD-SARA-001',
            'conversion_type' => 'SALE',
            'status' => ConversionStatus::APPROVED,
            'order_value' => '500.00',
            'currency' => 'SAR',
            'commission_value' => '75.00',
            'occurred_at' => now(),
            'verified_at' => now(),
        ]);

        Conversion::create([
            'campaign_id' => $campaign->id,
            'campaign_application_id' => $appA->id,
            'external_order_id' => 'ORD-SARA-002',
            'conversion_type' => 'SALE',
            'status' => ConversionStatus::PENDING,
            'order_value' => '500.00',
            'currency' => 'SAR',
            'commission_value' => '75.00',
            'occurred_at' => now(),
        ]);

        // Create CommissionSettlement record for the approved conversion
        $settleTx = LedgerTransaction::create([
            'transaction_number' => 'LTX-SETTLE-SARA-001',
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::COMMISSION_EARNING,
            'status' => LedgerTransactionStatus::POSTED,
            'source_type' => Conversion::class,
            'source_id' => $approvedConv->id,
            'idempotency_key' => 'idemp-settle-sara-1',
            'description' => 'تسوية عمولة تسويقية',
            'posted_at' => now(),
        ]);

        CommissionSettlement::create([
            'conversion_id' => $approvedConv->id,
            'ledger_transaction_id' => $settleTx->id,
            'marketer_wallet_id' => $walletSAR->id,
            'brand_wallet_id' => $this->walletService->getOrCreateWallet($this->brandUserA, Currency::SAR)->id,
            'commission_amount' => '75.0000',
            'currency' => Currency::SAR,
            'idempotency_key' => 'test-settle-sara-1',
            'settled_at' => now(),
            'reversed_at' => null,
        ]);

        // GET Marketer Wallet
        $response = $this->actingAs($this->marketerUserA)->get(route('marketer.wallet.index', ['currency' => 'SAR']));

        $response->assertOk();
        $response->assertSee('محفظتي وأرباح التسويق');
        $response->assertSee('سارة المسوقة (Marketer A)');
        $response->assertSee('300.00'); // Available balance
        $response->assertSee('50.00');  // Reserved balance in payout
        $response->assertSee('75.00');  // Approved & Settled metrics
        $response->assertSee('حملة الأزياء الصيفية'); // In earnings breakdown table!
        $response->assertSee('متجر الرياض التقني (Brand A)');
    }

    public function test_marketer_cannot_view_or_access_other_marketers_wallet_or_payouts(): void
    {
        $walletB = $this->walletService->getOrCreateWallet($this->marketerUserB, Currency::SAR);
        $walletB->update([
            'balance' => '8888.0000',
            'available_balance' => '8888.0000',
            'reserved_balance' => '0.0000',
        ]);

        // Marketer B creates a payout request
        $payoutB = $this->payoutService->createPayoutRequest(
            $this->marketerUserB,
            $walletB,
            '500.00',
            [
                'bank_name' => 'بنك الإنماء',
                'account_holder_name' => 'خالد المسوق',
                'account_reference' => 'SA9999000000000000000000',
                'idempotency_key' => 'payout-b-test-1',
            ]
        );

        // Marketer A visits their wallet
        $response = $this->actingAs($this->marketerUserA)->get(route('marketer.wallet.index', ['currency' => 'SAR']));

        $response->assertOk();
        $response->assertDontSee($payoutB->request_number);
        $response->assertDontSee('خالد المسوق (Marketer B)');
        $response->assertDontSee('8888.00');
    }

    public function test_marketer_payout_request_validation_and_tamper_proofing(): void
    {
        $walletA = $this->walletService->getOrCreateWallet($this->marketerUserA, Currency::SAR);
        $walletA->update([
            'balance' => '100.0000',
            'available_balance' => '100.0000',
            'reserved_balance' => '0.0000',
        ]);

        $walletB = $this->walletService->getOrCreateWallet($this->marketerUserB, Currency::SAR);

        // 1. Attempt to request payout targeting another user's wallet (ID tampering)
        $tamperResp = $this->actingAs($this->marketerUserA)->post(route('marketer.wallet.payout-request'), [
            'wallet_id' => $walletB->id,
            'amount' => '50.00',
            'bank_name' => 'الراجحي',
            'account_holder_name' => 'سارة',
            'account_reference' => 'SA4400001234567890123456',
        ]);

        $tamperResp->assertNotFound(); // Scoped query returns 404

        // 2. Attempt to request payout exceeding available balance
        $excessResp = $this->actingAs($this->marketerUserA)->post(route('marketer.wallet.payout-request'), [
            'wallet_id' => $walletA->id,
            'amount' => '250.00', // exceeds 100.00
            'bank_name' => 'الراجحي',
            'account_holder_name' => 'سارة',
            'account_reference' => 'SA4400001234567890123456',
        ]);

        $excessResp->assertSessionHasErrors(['amount']);
        $walletA->refresh();
        $this->assertEquals('100.0000', (string) $walletA->available_balance);
        $this->assertEquals('0.0000', (string) $walletA->reserved_balance);

        // 3. Valid payout request succeeds and holds funds immediately
        $validResp = $this->actingAs($this->marketerUserA)->post(route('marketer.wallet.payout-request'), [
            'wallet_id' => $walletA->id,
            'amount' => '40.00',
            'bank_name' => 'مصرف الراجحي',
            'account_holder_name' => 'سارة المسوقة',
            'account_reference' => 'SA4480000123456789012345',
        ]);

        $validResp->assertRedirect(route('marketer.wallet.index'));
        $validResp->assertSessionHas('status');

        $walletA->refresh();
        $this->assertEquals('60.0000', (string) $walletA->available_balance);
        $this->assertEquals('40.0000', (string) $walletA->reserved_balance);

        $payoutReq = PayoutRequest::where('user_id', $this->marketerUserA->id)->first();
        $this->assertNotNull($payoutReq);
        $this->assertEquals(PayoutRequestStatus::REQUESTED, $payoutReq->status);
        $this->assertEquals('40.0000', (string) $payoutReq->amount);
    }

    public function test_marketer_statement_supports_filters_and_pagination(): void
    {
        $walletSAR = $this->walletService->getOrCreateWallet($this->marketerUserA, Currency::SAR);

        $dummyTx = LedgerTransaction::create([
            'transaction_number' => 'LTX-TEST-PAG-001',
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::COMMISSION_EARNING,
            'status' => LedgerTransactionStatus::POSTED,
            'idempotency_key' => 'idemp-pag-test-01',
            'description' => 'حركات تجريبية للاختبار',
            'created_by' => $this->marketerUserA->id,
            'posted_at' => now(),
        ]);
        $payableAccount = \App\Models\LedgerAccount::where('code', '2030_MARKETER_PAYABLE_LIABILITY')->first();
        $dummyEntry = LedgerEntry::create([
            'ledger_transaction_id' => $dummyTx->id,
            'ledger_account_id' => $payableAccount->id,
            'wallet_id' => $walletSAR->id,
            'entry_type' => LedgerEntryType::CREDIT,
            'amount' => '10.0000',
            'currency' => Currency::SAR,
            'description' => 'قيد تجريبي',
        ]);

        // Create 20 wallet transactions
        for ($i = 1; $i <= 20; $i++) {
            WalletTransaction::create([
                'wallet_id' => $walletSAR->id,
                'ledger_transaction_id' => $dummyTx->id,
                'ledger_entry_id' => $dummyEntry->id,
                'entry_type' => LedgerEntryType::CREDIT,
                'amount' => '10.0000',
                'balance_after' => (string) ($i * 10) . '.0000',
                'reserved_after' => '0.0000',
                'currency' => 'SAR',
                'description' => "معاملة تجريبية رقم {$i}",
            ]);
        }

        // Check pagination on marketer statement
        $response = $this->actingAs($this->marketerUserA)->get(route('marketer.wallet.index', [
            'currency' => 'SAR',
            'statement_page' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('كشف حساب الحركات والعمليات المالية');
    }

    public function test_viewing_pages_is_immutable_and_does_not_mutate_balances(): void
    {
        $walletBrand = $this->walletService->getOrCreateWallet($this->brandUserA, Currency::SAR);
        $walletBrand->update([
            'balance' => '500.0000',
            'available_balance' => '500.0000',
            'reserved_balance' => '0.0000',
        ]);

        $walletMarketer = $this->walletService->getOrCreateWallet($this->marketerUserA, Currency::SAR);
        $walletMarketer->update([
            'balance' => '300.0000',
            'available_balance' => '300.0000',
            'reserved_balance' => '0.0000',
        ]);

        $entriesCountBefore = LedgerEntry::count();
        $txCountBefore = LedgerTransaction::count();

        // Repeated views to brand and marketer wallet pages
        $this->actingAs($this->brandUserA)->get(route('brand.wallet.index'));
        $this->actingAs($this->brandUserA)->get(route('brand.wallet.index', ['currency' => 'USD']));
        $this->actingAs($this->marketerUserA)->get(route('marketer.wallet.index'));
        $this->actingAs($this->marketerUserA)->get(route('marketer.wallet.index', ['currency' => 'AED']));

        // Assert zero mutation
        $this->assertEquals($entriesCountBefore, LedgerEntry::count());
        $this->assertEquals($txCountBefore, LedgerTransaction::count());

        $walletBrand->refresh();
        $this->assertEquals('500.0000', (string) $walletBrand->available_balance);

        $walletMarketer->refresh();
        $this->assertEquals('300.0000', (string) $walletMarketer->available_balance);
    }
}
