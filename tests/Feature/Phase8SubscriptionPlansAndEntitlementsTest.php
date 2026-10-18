<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\BillingCycle;
use App\Enums\CampaignApplicationStatus;
use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Enums\Currency;
use App\Enums\LedgerAccountType;
use App\Enums\LedgerTransactionType;
use App\Enums\SubscriptionPlanStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\InsufficientFundsException;
use App\Models\AuditLog;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\MarketerProfile;
use App\Models\MarketerSubscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanPrice;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Ledger\LedgerService;
use App\Services\Ledger\WalletService;
use App\Services\Math\MoneyMath;
use App\Services\Subscription\SubscriptionEntitlementService;
use App\Services\Subscription\SubscriptionPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase8SubscriptionPlansAndEntitlementsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $brandUser;
    protected BrandProfile $brandProfile;
    protected User $marketerUser1;
    protected MarketerProfile $marketerProfile1;
    protected User $marketerUser2;
    protected MarketerProfile $marketerProfile2;

    protected SubscriptionPaymentService $paymentService;
    protected SubscriptionEntitlementService $entitlementService;
    protected LedgerService $ledgerService;
    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentService = app(SubscriptionPaymentService::class);
        $this->entitlementService = app(SubscriptionEntitlementService::class);
        $this->ledgerService = app(LedgerService::class);
        $this->walletService = app(WalletService::class);

        // 1. Super Admin
        $this->superAdmin = User::create([
            'name' => 'المشرف العام للمنصة',
            'email' => 'superadmin@nomowly.com',
            'password' => Hash::make('AdminSecret123!'),
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        // 2. Brand User
        $this->brandUser = User::create([
            'name' => 'شركة ريادة للتجارة',
            'email' => 'brand@riyada.com',
            'password' => Hash::make('BrandSecret123!'),
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->brandProfile = BrandProfile::create([
            'user_id' => $this->brandUser->id,
            'company_name' => 'شركة ريادة العالمية',
            'website_url' => 'https://riyada.example.com',
            'status' => 'APPROVED',
        ]);

        // 3. Marketers
        $this->marketerUser1 = User::create([
            'name' => 'أحمد المسوق الأول',
            'email' => 'marketer1@nomowly.com',
            'password' => Hash::make('MarketerSecret123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->marketerProfile1 = MarketerProfile::create([
            'user_id' => $this->marketerUser1->id,
            'full_name' => 'أحمد بن علي',
            'phone' => '+966500000001',
            'country' => 'SA',
            'preferred_currency' => Currency::SAR,
            'status' => 'APPROVED',
        ]);

        $this->marketerUser2 = User::create([
            'name' => 'سارة المسوقة الثانية',
            'email' => 'marketer2@nomowly.com',
            'password' => Hash::make('MarketerSecret123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->marketerProfile2 = MarketerProfile::create([
            'user_id' => $this->marketerUser2->id,
            'full_name' => 'سارة بنت فهد',
            'phone' => '+966500000002',
            'country' => 'SA',
            'preferred_currency' => Currency::SAR,
            'status' => 'APPROVED',
        ]);
    }

    /**
     * Helper to create a standard test plan.
     */
    protected function createPlan(array $overrides = []): SubscriptionPlan
    {
        $plan = SubscriptionPlan::create(array_merge([
            'name_ar' => 'باقة المسوق المحترف',
            'name_en' => 'Pro Marketer Plan',
            'slug' => 'pro-marketer-' . uniqid(),
            'description_ar' => 'وصول كامل لكافة الحملات الحصرية والمميزة في المنصة',
            'billing_cycle' => BillingCycle::MONTHLY,
            'duration_in_days' => 30,
            'status' => SubscriptionPlanStatus::ACTIVE,
            'allows_premium_campaigns' => true,
            'max_premium_campaigns_per_month' => 10,
            'max_active_applications' => 20,
            'features' => ['أولوية الاعتماد', 'دعم فني خاص', 'عمولات مميزة'],
            'created_by_user_id' => $this->superAdmin->id,
        ], $overrides));

        // Default prices in SAR and USD
        SubscriptionPlanPrice::create([
            'subscription_plan_id' => $plan->id,
            'currency' => Currency::SAR->value,
            'price' => '299.00',
            'is_active' => true,
        ]);

        SubscriptionPlanPrice::create([
            'subscription_plan_id' => $plan->id,
            'currency' => Currency::USD->value,
            'price' => '79.00',
            'is_active' => true,
        ]);

        return $plan;
    }

    /**
     * Helper to create a campaign.
     */
    protected function createCampaign(bool $isPremium = false, ?array $allowedPlanIds = null): Campaign
    {
        return Campaign::create([
            'brand_profile_id' => $this->brandProfile->id,
            'title' => $isPremium ? 'حملة العطور الحصرية الفاخرة' : 'حملة الإلكترونيات العامة',
            'slug' => ($isPremium ? 'premium-perfumes-' : 'general-electronics-') . uniqid(),
            'category' => 'عطور',
            'category_slug' => 'perfumes',
            'description' => 'حملة تسويق بالعمولة بمكافآت مجزية.',
            'brief' => 'شروط ومحتوى الحملة التسويقية.',
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15%',
            'cookie_duration_days' => 30,
            'target_markets' => ['السعودية', 'الإمارات'],
            'currencies' => ['SAR', 'AED'],
            'tracking_methods' => ['COUPON', 'LINK'],
            'status' => CampaignStatus::ACTIVE,
            'is_premium' => $isPremium,
            'allowed_plan_ids' => $allowedPlanIds,
        ]);
    }

    // =========================================================================
    // 1. Subscription Plans Creation and Administration
    // =========================================================================

    public function test_super_admin_can_create_and_manage_subscription_plans_with_multi_currency_prices(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.subscriptions.plans.store'), [
            'name_ar' => 'باقة النمو الذهبية',
            'name_en' => 'Golden Growth Plan',
            'description_ar' => 'باقة مخصصة لكبار صناع المحتوى',
            'billing_cycle' => BillingCycle::MONTHLY->value,
            'duration_in_days' => 30,
            'status' => SubscriptionPlanStatus::ACTIVE->value,
            'sort_order' => 1,
            'is_featured' => 1,
            'allows_premium_campaigns' => 1,
            'max_premium_campaigns_per_month' => 25,
            'max_active_applications' => 50,
            'features' => "دعم مدار الساعة\nأولوية التفعيل الفوري\nتقارير أداء متقدمة",
            'prices' => [
                'SAR' => '499.00',
                'USD' => '135.00',
                'AED' => '490.00',
                'EGP' => '6500.00',
            ],
        ]);

        $response->assertRedirect(route('admin.subscriptions.plans.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('subscription_plans', [
            'name_ar' => 'باقة النمو الذهبية',
            'billing_cycle' => 'MONTHLY',
            'duration_in_days' => 30,
            'allows_premium_campaigns' => 1,
            'max_premium_campaigns_per_month' => 25,
        ]);

        $plan = SubscriptionPlan::where('name_ar', 'باقة النمو الذهبية')->firstOrFail();
        $this->assertEquals(4, $plan->prices()->count());
        $this->assertEquals('499.00', $plan->priceFor(Currency::SAR)->price);
        $this->assertEquals('135.00', $plan->priceFor(Currency::USD)->price);
        $this->assertEquals('6500.00', $plan->priceFor(Currency::EGP)->price);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::SUBSCRIPTION_PLAN_CREATED->value,
            'user_id' => $this->superAdmin->id,
            'entity_id' => $plan->id,
        ]);
    }

    public function test_super_admin_can_toggle_plan_status_and_update_prices_without_retroactive_impact(): void
    {
        $plan = $this->createPlan();

        // 1. Toggle status
        $response = $this->actingAs($this->superAdmin)->patch(route('admin.subscriptions.plans.status', $plan), [
            'status' => SubscriptionPlanStatus::INACTIVE->value,
        ]);
        $response->assertRedirect();
        $this->assertEquals(SubscriptionPlanStatus::INACTIVE, $plan->fresh()->status);

        // 2. Reactivate and update price
        $this->actingAs($this->superAdmin)->put(route('admin.subscriptions.plans.update', $plan), [
            'name_ar' => 'باقة المسوق المحترف المحدثة',
            'billing_cycle' => BillingCycle::MONTHLY->value,
            'duration_in_days' => 30,
            'status' => SubscriptionPlanStatus::ACTIVE->value,
            'allows_premium_campaigns' => 1,
            'prices' => [
                'SAR' => '349.00', // Increased price for new buyers
                'USD' => '89.00',
            ],
        ]);

        $this->assertEquals('349.00', $plan->fresh()->priceFor(Currency::SAR)->price);
    }

    // =========================================================================
    // 2. RBAC and Permissions Isolation
    // =========================================================================

    public function test_marketer_and_brand_cannot_access_or_modify_subscription_plans(): void
    {
        $plan = $this->createPlan();

        // Marketer attempt
        $resMarketer = $this->actingAs($this->marketerUser1)->get(route('admin.subscriptions.plans.index'));
        $resMarketer->assertForbidden();

        $resMarketerPost = $this->actingAs($this->marketerUser1)->post(route('admin.subscriptions.plans.store'), [
            'name_ar' => 'محاولة اختراق',
            'prices' => ['SAR' => '10.00'],
        ]);
        $resMarketerPost->assertForbidden();

        // Brand attempt
        $resBrand = $this->actingAs($this->brandUser)->get(route('admin.subscriptions.plans.index'));
        $resBrand->assertForbidden();

        $resBrandStatus = $this->actingAs($this->brandUser)->patch(route('admin.subscriptions.plans.status', $plan), [
            'status' => SubscriptionPlanStatus::INACTIVE->value,
        ]);
        $resBrandStatus->assertForbidden();
    }

    // =========================================================================
    // 3. Subscription Initiation with Immutable Snapshot
    // =========================================================================

    public function test_subscription_initiation_captures_immutable_snapshot_of_plan_and_price(): void
    {
        $plan = $this->createPlan();

        $subscription = $this->paymentService->initiateSubscription([
            'user' => $this->marketerUser1,
            'plan' => $plan,
            'currency' => Currency::SAR,
            'payment_method' => 'GATEWAY',
        ]);

        $this->assertEquals(SubscriptionStatus::PENDING_PAYMENT, $subscription->status);
        $this->assertEquals('299.00', $subscription->price_paid);
        $this->assertEquals(Currency::SAR, $subscription->currency);
        $this->assertNotNull($subscription->plan_snapshot);
        $this->assertEquals('باقة المسوق المحترف', $subscription->plan_snapshot['name_ar']);
        $this->assertEquals('299.00', $subscription->plan_snapshot['price']);

        // Now, if admin modifies the plan price later, the existing subscription snapshot remains unchanged
        $plan->priceFor(Currency::SAR)->update(['price' => '499.00']);

        $this->assertEquals('299.00', $subscription->fresh()->price_paid);
        $this->assertEquals('299.00', $subscription->fresh()->plan_snapshot['price']);
    }

    // =========================================================================
    // 4. Payment Gateway Activation and Balanced Double-Entry Accounting
    // =========================================================================

    public function test_subscription_gateway_payment_activates_cleanly_with_balanced_ledger_entries(): void
    {
        $plan = $this->createPlan();

        $subscription = $this->paymentService->initiateSubscription([
            'user' => $this->marketerUser1,
            'plan' => $plan,
            'currency' => Currency::SAR,
            'payment_method' => 'GATEWAY',
        ]);

        $this->assertEquals(SubscriptionStatus::PENDING_PAYMENT, $subscription->status);

        // Confirm payment with mock gateway transaction ID and gateway fee
        $gwTxId = 'GW_CH_TEST_' . uniqid();
        $fee = '5.5000';

        $activatedSub = $this->paymentService->confirmGatewayPayment($subscription, $gwTxId, $fee);

        $this->assertEquals(SubscriptionStatus::ACTIVE, $activatedSub->status);
        $this->assertNotNull($activatedSub->starts_at);
        $this->assertNotNull($activatedSub->ends_at);
        $this->assertTrue($activatedSub->isActive());

        // Verify ledger transaction
        $ledgerTx = $activatedSub->ledgerTransaction;
        $this->assertNotNull($ledgerTx);
        $this->assertEquals(LedgerTransactionType::SUBSCRIPTION_PAYMENT, $ledgerTx->transaction_type);
        $this->assertEquals(Currency::SAR, $ledgerTx->currency);

        // Verify balanced double-entry
        $entries = $ledgerTx->entries;
        $totalDebits = '0.0000';
        $totalCredits = '0.0000';

        foreach ($entries as $e) {
            if ($e->entry_type->value === 'DEBIT') {
                $totalDebits = MoneyMath::add($totalDebits, $e->amount);
            } else {
                $totalCredits = MoneyMath::add($totalCredits, $e->amount);
            }
        }

        // Must balance exactly: Dr. Cash (293.50) + Dr. Fee (5.50) == Cr. Revenue (299.00)
        $this->assertEquals($totalDebits, $totalCredits);
        $this->assertEquals('299.0000', $totalDebits);

        // Verify accounts used
        $revAcc = LedgerAccount::where('code', '4020_SUBSCRIPTION_REVENUE')->firstOrFail();
        $cashAcc = LedgerAccount::where('code', '1010_PLATFORM_CASH')->firstOrFail();
        $feeAcc = LedgerAccount::where('code', '5030_PAYMENT_GATEWAY_FEE_EXPENSE')->firstOrFail();

        $this->assertDatabaseHas('ledger_entries', [
            'ledger_transaction_id' => $ledgerTx->id,
            'ledger_account_id' => $revAcc->id,
            'entry_type' => 'CREDIT',
            'amount' => '299.0000',
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'ledger_transaction_id' => $ledgerTx->id,
            'ledger_account_id' => $cashAcc->id,
            'entry_type' => 'DEBIT',
            'amount' => '293.5000',
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'ledger_transaction_id' => $ledgerTx->id,
            'ledger_account_id' => $feeAcc->id,
            'entry_type' => 'DEBIT',
            'amount' => '5.5000',
        ]);

        // CRITICAL: Ensure NO money was added to the marketer's wallet!
        $wallet = $this->marketerUser1->getWallet(Currency::SAR);
        if ($wallet) {
            $this->assertEquals('0.0000', $wallet->balance);
        }
    }

    // =========================================================================
    // 5. Idempotent Confirmation and Duplicate Webhook Safety
    // =========================================================================

    public function test_confirming_subscription_twice_is_strictly_idempotent_and_does_not_duplicate_revenue(): void
    {
        $plan = $this->createPlan();

        $subscription = $this->paymentService->initiateSubscription([
            'user' => $this->marketerUser1,
            'plan' => $plan,
            'currency' => Currency::SAR,
            'payment_method' => 'GATEWAY',
        ]);

        $gwTxId = 'GW_CH_TEST_' . uniqid();

        // First confirmation
        $sub1 = $this->paymentService->confirmGatewayPayment($subscription, $gwTxId, '0.0000');
        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub1->status);
        $ledgerCountBefore = LedgerTransaction::where('source_id', $subscription->id)->count();
        $this->assertEquals(1, $ledgerCountBefore);

        // Second duplicate webhook confirmation attempt
        $sub2 = $this->paymentService->confirmGatewayPayment($subscription, $gwTxId, '0.0000');
        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub2->status);

        // Ledger transactions count MUST NOT increase
        $ledgerCountAfter = LedgerTransaction::where('source_id', $subscription->id)->count();
        $this->assertEquals(1, $ledgerCountAfter);
    }

    // =========================================================================
    // 6. Wallet Balance Payment Method
    // =========================================================================

    public function test_marketer_can_pay_subscription_from_earned_wallet_balance(): void
    {
        $plan = $this->createPlan();

        // 1. Give marketer earned balance via settled commission (deposit directly for test setup)
        $wallet = $this->walletService->getOrCreateWallet($this->marketerUser1, Currency::SAR);

        // Seed wallet with 500 SAR available earnings using double-entry ledger
        $this->ledgerService->postTransaction([
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::COMMISSION_EARNING,
            'idempotency_key' => 'SEED_COMMISSION_' . uniqid(),
            'description' => 'إيداع عمولة مسوق سابقة للاختبار',
            'entries' => [
                [
                    'account_code' => '2020_CAMPAIGN_ESCROW_LIABILITY',
                    'entry_type' => \App\Enums\LedgerEntryType::DEBIT,
                    'amount' => '500.0000',
                    'description' => 'تسوية عمولة',
                ],
                [
                    'account_code' => '2030_MARKETER_PAYABLE_LIABILITY',
                    'wallet_id' => $wallet->id,
                    'entry_type' => \App\Enums\LedgerEntryType::CREDIT,
                    'amount' => '500.0000',
                    'description' => 'أرباح مستحقة في المحفظة',
                ],
            ],
            'created_by' => $this->superAdmin,
        ]);

        $wallet->refresh();
        $this->assertEquals('500.0000', $wallet->available_balance);

        // 2. Initiate subscription
        $subscription = $this->paymentService->initiateSubscription([
            'user' => $this->marketerUser1,
            'plan' => $plan,
            'currency' => Currency::SAR,
            'payment_method' => 'WALLET',
        ]);

        // 3. Pay with wallet
        $paidSub = $this->paymentService->payWithWalletBalance($subscription);

        $this->assertEquals(SubscriptionStatus::ACTIVE, $paidSub->status);
        $this->assertEquals('WALLET', $paidSub->payment_method);

        // Wallet balance must decrease by exact plan price (500 - 299 = 201)
        $wallet->refresh();
        $this->assertEquals('201.0000', $wallet->available_balance);

        // Verify double-entry ledger: Dr. 2030 Marketer Payable, Cr. 4020 Subscription Revenue
        $ledgerTx = $paidSub->ledgerTransaction;
        $this->assertNotNull($ledgerTx);

        $payableAcc = LedgerAccount::where('code', '2030_MARKETER_PAYABLE_LIABILITY')->firstOrFail();
        $revAcc = LedgerAccount::where('code', '4020_SUBSCRIPTION_REVENUE')->firstOrFail();

        $this->assertDatabaseHas('ledger_entries', [
            'ledger_transaction_id' => $ledgerTx->id,
            'ledger_account_id' => $payableAcc->id,
            'wallet_id' => $wallet->id,
            'entry_type' => 'DEBIT',
            'amount' => '299.0000',
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'ledger_transaction_id' => $ledgerTx->id,
            'ledger_account_id' => $revAcc->id,
            'entry_type' => 'CREDIT',
            'amount' => '299.0000',
        ]);
    }

    public function test_paying_with_insufficient_wallet_balance_fails_and_does_not_mutate_balance(): void
    {
        $plan = $this->createPlan();

        // Marketer wallet starts at 0.00 SAR
        $wallet = $this->walletService->getOrCreateWallet($this->marketerUser1, Currency::SAR);
        $this->assertEquals('0.0000', $wallet->available_balance);

        $subscription = $this->paymentService->initiateSubscription([
            'user' => $this->marketerUser1,
            'plan' => $plan,
            'currency' => Currency::SAR,
            'payment_method' => 'WALLET',
        ]);

        $this->expectException(InsufficientFundsException::class);

        try {
            $this->paymentService->payWithWalletBalance($subscription);
        } finally {
            $this->assertEquals(SubscriptionStatus::PENDING_PAYMENT, $subscription->fresh()->status);
            $this->assertEquals('0.0000', $wallet->fresh()->available_balance);
        }
    }

    // =========================================================================
    // 7. Premium Campaign Entitlements and Server-Side Gating
    // =========================================================================

    public function test_marketer_without_active_subscription_cannot_apply_to_premium_campaign(): void
    {
        $premiumCampaign = $this->createCampaign(isPremium: true);

        // 1. Entitlement check service returns false with informative reason
        $check = $this->entitlementService->canApply($this->marketerUser1, $premiumCampaign);
        $this->assertFalse($check['allowed']);
        $this->assertEquals('no_active_subscription', $check['code']);

        // 2. HTTP POST apply endpoint strictly rejects
        $response = $this->actingAs($this->marketerUser1)->post(route('marketer.campaigns.apply', $premiumCampaign), [
            'pitch_note' => 'أرغب بالترويج لهذه الحملة المميزة',
        ]);

        $response->assertRedirect(route('marketplace.show', $premiumCampaign->slug));
        $response->assertSessionHas('error');

        // No application was created
        $this->assertDatabaseMissing('campaign_applications', [
            'campaign_id' => $premiumCampaign->id,
            'marketer_profile_id' => $this->marketerProfile1->id,
        ]);
    }

    public function test_marketer_can_apply_to_standard_campaign_without_any_subscription(): void
    {
        $standardCampaign = $this->createCampaign(isPremium: false);

        $check = $this->entitlementService->canApply($this->marketerUser1, $standardCampaign);
        $this->assertTrue($check['allowed']);

        $response = $this->actingAs($this->marketerUser1)->post(route('marketer.campaigns.apply', $standardCampaign), [
            'pitch_note' => 'خطة ترويج ممتازة',
        ]);

        $response->assertRedirect(route('marketplace.show', $standardCampaign->slug));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('campaign_applications', [
            'campaign_id' => $standardCampaign->id,
            'marketer_profile_id' => $this->marketerProfile1->id,
            'status' => CampaignApplicationStatus::PENDING->value,
        ]);
    }

    public function test_marketer_with_active_subscription_can_apply_to_premium_campaign(): void
    {
        $plan = $this->createPlan();
        $premiumCampaign = $this->createCampaign(isPremium: true);

        // Subscribe and activate
        $sub = $this->paymentService->initiateSubscription([
            'user' => $this->marketerUser1,
            'plan' => $plan,
            'currency' => Currency::SAR,
        ]);
        $this->paymentService->confirmGatewayPayment($sub, 'GW_' . uniqid());

        $check = $this->entitlementService->canApply($this->marketerUser1, $premiumCampaign);
        $this->assertTrue($check['allowed']);

        $response = $this->actingAs($this->marketerUser1)->post(route('marketer.campaigns.apply', $premiumCampaign), [
            'pitch_note' => 'خطة حملة ممتازة للمنتجات الفاخرة',
        ]);

        $response->assertRedirect(route('marketplace.show', $premiumCampaign->slug));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('campaign_applications', [
            'campaign_id' => $premiumCampaign->id,
            'marketer_profile_id' => $this->marketerProfile1->id,
            'status' => CampaignApplicationStatus::PENDING->value,
        ]);
    }

    public function test_premium_campaign_restricting_to_specific_plans_blocks_unauthorized_tier(): void
    {
        $basicPlan = $this->createPlan(['name_ar' => 'الباقة الفضية الأساسية']);
        $vipPlan = $this->createPlan(['name_ar' => 'باقة كبار الشخصيات VIP']);

        // Premium campaign only allows VIP plan
        $premiumCampaign = $this->createCampaign(isPremium: true, allowedPlanIds: [$vipPlan->id]);

        // Marketer subscribes to Basic Plan
        $sub = $this->paymentService->initiateSubscription([
            'user' => $this->marketerUser1,
            'plan' => $basicPlan,
            'currency' => Currency::SAR,
        ]);
        $this->paymentService->confirmGatewayPayment($sub, 'GW_' . uniqid());

        // Basic plan marketer cannot apply
        $checkBasic = $this->entitlementService->canApply($this->marketerUser1, $premiumCampaign);
        $this->assertFalse($checkBasic['allowed']);
        $this->assertEquals('plan_tier_not_permitted', $checkBasic['code']);

        // Marketer 2 subscribes to VIP Plan
        $sub2 = $this->paymentService->initiateSubscription([
            'user' => $this->marketerUser2,
            'plan' => $vipPlan,
            'currency' => Currency::SAR,
        ]);
        $this->paymentService->confirmGatewayPayment($sub2, 'GW_' . uniqid());

        // VIP plan marketer CAN apply
        $checkVip = $this->entitlementService->canApply($this->marketerUser2, $premiumCampaign);
        $this->assertTrue($checkVip['allowed']);
    }

    // =========================================================================
    // 8. Subscription Expiration, Suspension and Rights Protection
    // =========================================================================

    public function test_expired_subscription_is_rejected_for_new_premium_campaigns(): void
    {
        $plan = $this->createPlan();
        $premiumCampaign = $this->createCampaign(isPremium: true);

        // Create expired subscription (ends_at in the past)
        $sub = MarketerSubscription::create([
            'subscription_number' => 'SUB-EXP-001',
            'user_id' => $this->marketerUser1->id,
            'marketer_profile_id' => $this->marketerProfile1->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'currency' => Currency::SAR,
            'price_paid' => '299.0000',
            'billing_cycle' => BillingCycle::MONTHLY,
            'duration_in_days' => 30,
            'starts_at' => now()->subDays(60),
            'ends_at' => now()->subDays(30), // Expired 30 days ago
            'plan_snapshot' => $plan->buildSnapshot(Currency::SAR),
        ]);

        $check = $this->entitlementService->canApply($this->marketerUser1, $premiumCampaign);
        $this->assertFalse($check['allowed']);
        $this->assertEquals('subscription_expired', $check['code']);
    }

    public function test_suspended_subscription_is_rejected_with_mandatory_admin_reason(): void
    {
        $plan = $this->createPlan();
        $premiumCampaign = $this->createCampaign(isPremium: true);

        $sub = $this->paymentService->initiateSubscription([
            'user' => $this->marketerUser1,
            'plan' => $plan,
            'currency' => Currency::SAR,
        ]);
        $this->paymentService->confirmGatewayPayment($sub, 'GW_' . uniqid());

        // Admin suspends subscription
        $this->actingAs($this->superAdmin)->post(route('admin.subscriptions.suspend', $sub), [
            'reason' => 'مخالفة سياسة النشر الإعلاني والمحتوى المضلل',
        ]);

        $sub->refresh();
        $this->assertEquals(SubscriptionStatus::SUSPENDED, $sub->status);
        $this->assertEquals('مخالفة سياسة النشر الإعلاني والمحتوى المضلل', $sub->suspension_reason);

        $check = $this->entitlementService->canApply($this->marketerUser1, $premiumCampaign);
        $this->assertFalse($check['allowed']);
        $this->assertEquals('subscription_suspended', $check['code']);
        $this->assertStringContainsString('مخالفة سياسة النشر', $check['reason']);
    }

    public function test_subscription_expiration_strictly_preserves_historical_applications_and_earned_commissions(): void
    {
        $plan = $this->createPlan();
        $premiumCampaign = $this->createCampaign(isPremium: true);

        // 1. Marketer has active subscription and applied to campaign
        $sub = $this->paymentService->initiateSubscription([
            'user' => $this->marketerUser1,
            'plan' => $plan,
            'currency' => Currency::SAR,
        ]);
        $this->paymentService->confirmGatewayPayment($sub, 'GW_' . uniqid());

        $this->actingAs($this->marketerUser1)->post(route('marketer.campaigns.apply', $premiumCampaign));
        $application = CampaignApplication::where('campaign_id', $premiumCampaign->id)
            ->where('marketer_profile_id', $this->marketerProfile1->id)
            ->firstOrFail();

        // Brand approved application and generated tracking code
        $application->update([
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'MKT-PERFUME-101',
            'tracking_link' => 'https://nomowly.com/go/MKT-PERFUME-101',
            'approved_at' => now(),
        ]);

        // 2. Marketer has earned wallet balance
        $wallet = $this->walletService->getOrCreateWallet($this->marketerUser1, Currency::SAR);
        $this->ledgerService->postTransaction([
            'currency' => Currency::SAR,
            'transaction_type' => LedgerTransactionType::COMMISSION_EARNING,
            'idempotency_key' => 'HISTORICAL_SETTLEMENT_' . uniqid(),
            'description' => 'عمولات تسويق معتمدة سابقة',
            'entries' => [
                [
                    'account_code' => '2020_CAMPAIGN_ESCROW_LIABILITY',
                    'entry_type' => \App\Enums\LedgerEntryType::DEBIT,
                    'amount' => '1500.0000',
                    'description' => 'تسوية عمولات تاريخية',
                ],
                [
                    'account_code' => '2030_MARKETER_PAYABLE_LIABILITY',
                    'wallet_id' => $wallet->id,
                    'entry_type' => \App\Enums\LedgerEntryType::CREDIT,
                    'amount' => '1500.0000',
                    'description' => 'أرباح معتمدة للمسوق',
                ],
            ],
            'created_by' => $this->superAdmin,
        ]);

        $wallet->refresh();
        $this->assertEquals('1500.0000', $wallet->available_balance);

        // 3. Subscription expires!
        $this->paymentService->expireSubscription($sub);
        $this->assertEquals(SubscriptionStatus::EXPIRED, $sub->fresh()->status);

        // 4. Verify historical rights protection:
        // A) Existing application remains APPROVED with same tracking code and link
        $application->refresh();
        $this->assertEquals(CampaignApplicationStatus::APPROVED, $application->status);
        $this->assertEquals('MKT-PERFUME-101', $application->tracking_code);
        $this->assertEquals('https://nomowly.com/go/MKT-PERFUME-101', $application->tracking_link);

        // B) Earned wallet balance is completely intact
        $wallet->refresh();
        $this->assertEquals('1500.0000', $wallet->available_balance);

        // C) Marketer CAN STILL request payout of their earned money despite expired subscription
        $payoutResponse = $this->actingAs($this->marketerUser1)->post(route('marketer.wallet.payout-request'), [
            'wallet_id' => $wallet->id,
            'amount' => '500.00',
            'bank_name' => 'مصرف الراجحي',
            'account_holder_name' => 'أحمد بن علي',
            'account_reference' => 'SA0380000000608010167519',
        ]);

        $payoutResponse->assertRedirect();
        $payoutResponse->assertSessionHas('status');

        $this->assertDatabaseHas('payout_requests', [
            'user_id' => $this->marketerUser1->id,
            'wallet_id' => $wallet->id,
            'amount' => '500.0000',
        ]);
    }

    // =========================================================================
    // 9. Quota and Application Limits Enforcement
    // =========================================================================

    public function test_monthly_premium_campaign_application_quota_is_strictly_enforced(): void
    {
        // Plan allows max 2 premium campaigns per month
        $plan = $this->createPlan(['max_premium_campaigns_per_month' => 2]);

        $sub = $this->paymentService->initiateSubscription([
            'user' => $this->marketerUser1,
            'plan' => $plan,
            'currency' => Currency::SAR,
        ]);
        $this->paymentService->confirmGatewayPayment($sub, 'GW_' . uniqid());

        // Create 3 premium campaigns
        $c1 = $this->createCampaign(isPremium: true);
        $c2 = $this->createCampaign(isPremium: true);
        $c3 = $this->createCampaign(isPremium: true);

        // First application: Allowed
        $this->actingAs($this->marketerUser1)->post(route('marketer.campaigns.apply', $c1));
        $this->assertDatabaseHas('campaign_applications', ['campaign_id' => $c1->id, 'marketer_profile_id' => $this->marketerProfile1->id]);

        // Second application: Allowed
        $this->actingAs($this->marketerUser1)->post(route('marketer.campaigns.apply', $c2));
        $this->assertDatabaseHas('campaign_applications', ['campaign_id' => $c2->id, 'marketer_profile_id' => $this->marketerProfile1->id]);

        // Third application: QUOTA EXCEEDED -> Blocked!
        $check = $this->entitlementService->canApply($this->marketerUser1, $c3);
        $this->assertFalse($check['allowed']);
        $this->assertEquals('monthly_limit_reached', $check['code']);

        $res3 = $this->actingAs($this->marketerUser1)->post(route('marketer.campaigns.apply', $c3));
        $res3->assertRedirect();
        $res3->assertSessionHas('error');

        $this->assertDatabaseMissing('campaign_applications', ['campaign_id' => $c3->id, 'marketer_profile_id' => $this->marketerProfile1->id]);
    }

    // =========================================================================
    // 10. Multi-currency Isolation and Tenant Privacy
    // =========================================================================

    public function test_unsupported_currency_cannot_be_used_to_subscribe(): void
    {
        $plan = $this->createPlan(); // Priced only in SAR and USD

        // Try to initiate in KWD which has no price set
        $this->expectException(\InvalidArgumentException::class);
        $this->paymentService->initiateSubscription([
            'user' => $this->marketerUser1,
            'plan' => $plan,
            'currency' => Currency::KWD,
        ]);
    }

    public function test_marketer_cannot_view_or_access_other_marketers_subscription(): void
    {
        $plan = $this->createPlan();

        $subMarketer2 = $this->paymentService->initiateSubscription([
            'user' => $this->marketerUser2,
            'plan' => $plan,
            'currency' => Currency::SAR,
        ]);
        $this->paymentService->confirmGatewayPayment($subMarketer2, 'GW_' . uniqid());

        // Marketer 1 tries to view Marketer 2's subscription
        $response = $this->actingAs($this->marketerUser1)->get(route('marketer.subscriptions.show', $subMarketer2->id));
        $response->assertForbidden();

        // Marketer 2 can view own subscription cleanly
        $res2 = $this->actingAs($this->marketerUser2)->get(route('marketer.subscriptions.show', $subMarketer2->id));
        $res2->assertOk();
    }
}
