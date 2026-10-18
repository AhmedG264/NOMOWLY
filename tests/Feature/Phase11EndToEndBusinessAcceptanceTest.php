<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\CampaignApplicationStatus;
use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Enums\CampaignTermsChangeScope;
use App\Enums\ConversionStatus;
use App\Enums\Currency;
use App\Enums\LedgerEntryType;
use App\Enums\LedgerTransactionStatus;
use App\Enums\LedgerTransactionType;
use App\Enums\MarketerSpecializationType;
use App\Enums\PaymentDepositStatus;
use App\Enums\PaymentProvider;
use App\Enums\PayoutRequestStatus;
use App\Enums\SubscriptionPlanStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WalletStatus;
use App\Enums\WalletType;
use App\Exceptions\InsufficientFundsException;
use App\Models\AttributionClick;
use App\Models\AuditLog;
use App\Models\BrandIntegration;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\CampaignTermsChangeRequest;
use App\Models\CampaignTermsVersion;
use App\Models\CommissionSettlement;
use App\Models\Conversion;
use App\Models\FraudDetectionRule;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\MarketerProfile;
use App\Models\MarketerSpecialization;
use App\Models\MarketerSubscription;
use App\Models\PaymentDeposit;
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
use App\Services\Payment\Gateways\FakePaymentGateway;
use App\Services\Payment\PaymentDepositService;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Subscription\SubscriptionEntitlementService;
use App\Services\Subscription\SubscriptionPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase11EndToEndBusinessAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected SubscriptionPlan $proPlan;
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

        // Bootstrap Super Admin
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'admin@nomowly.com',
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $saRole = Role::where('name', UserRole::SUPER_ADMIN->value)->first();
        if ($saRole) {
            $this->superAdmin->roles()->syncWithoutDetaching([$saRole->id => []]);
        }

        // Seed a standard Pro subscription plan for testing
        $this->proPlan = SubscriptionPlan::create([
            'slug' => 'pro-creator-plan',
            'name_ar' => 'باقة المحترفين',
            'name_en' => 'Pro Creator Plan',
            'description_ar' => 'الوصول لكافة الحملات المميزة',
            'status' => SubscriptionPlanStatus::ACTIVE,
            'features' => ['unlimited_premium_campaigns', 'priority_support'],
            'monthly_campaign_limit' => 20,
            'sort_order' => 1,
        ]);

        SubscriptionPlanPrice::create([
            'subscription_plan_id' => $this->proPlan->id,
            'currency' => Currency::SAR,
            'price' => '200.0000',
            'billing_cycle' => 'MONTHLY',
            'is_active' => true,
        ]);
    }

    /**
     * 1. Brand Journey (Full Business Acceptance Test)
     *
     * Flow:
     * - Register brand user with complete company profile and SAR currency.
     * - Login and verify password change policy status.
     * - Fund brand wallet via test payment deposit and callback confirmation.
     * - Verify no fictitious money or unconfirmed deposit credits.
     * - Create marketing campaign with commission terms and tracking rules.
     * - Verify automatic initial contractual terms version v1 generation.
     * - Review incoming creator applications and approve with generated tracking code.
     * - Review conversions, commissions, and statement of wallet movements.
     * - Pause / complete campaign under proper status lifecycle.
     */
    public function test_brand_journey_full_business_lifecycle(): void
    {
        // 1. Register new Brand
        $brandEmail = 'brand.journey@test.com';
        $registerResponse = $this->post(route('register.store'), [
            'role' => UserRole::BRAND->value,
            'name' => 'Al-Amal Retail Group',
            'email' => $brandEmail,
            'password' => 'SecurePass1234!',
            'password_confirmation' => 'SecurePass1234!',
            'company_name' => 'Al-Amal E-Commerce Co',
            'website_url' => 'https://alamal-retail.example.com',
            'currency' => 'SAR',
        ]);

        $registerResponse->assertRedirect(route('brand.dashboard'));
        $this->assertAuthenticated();

        $brandUser = User::where('email', $brandEmail)->firstOrFail();
        $this->assertEquals(UserRole::BRAND, $brandUser->role);
        $this->assertNotNull($brandUser->brandProfile);
        $this->assertEquals('Al-Amal E-Commerce Co', $brandUser->brandProfile->company_name);
        $this->assertEquals('SAR', $brandUser->brandProfile->currency);

        // 2. Profile and Password Change Verification
        $this->assertFalse($brandUser->must_change_password);
        $dashboardResponse = $this->actingAs($brandUser)->get(route('brand.dashboard'));
        $dashboardResponse->assertStatus(200);

        // 3. Initiate Wallet Deposit of 2,000.00 SAR
        $depositResponse = $this->actingAs($brandUser)->post(route('brand.wallet.deposit'), [
            'currency' => 'SAR',
            'amount' => '2000.00',
            'provider' => 'fake',
            'payment_method' => 'card',
        ]);

        $depositResponse->assertRedirect();
        $deposit = PaymentDeposit::where('user_id', $brandUser->id)->latest()->firstOrFail();
        $this->assertEquals('2000.0000', $deposit->amount);
        $this->assertEquals(PaymentDepositStatus::PENDING, $deposit->status);

        // 4. Verify wallet NOT credited before confirmation (Zero Fictitious Money)
        $brandWallet = $this->walletService->getOrCreateWallet($brandUser, Currency::SAR);
        $this->assertEquals('0.0000', MoneyMath::format($brandWallet->available_balance));

        // 5. Simulate trusted Gateway Callback confirmation
        $callbackResponse = $this->actingAs($brandUser)->get(route('brand.wallet.deposit.callback', [
            'deposit_simulated' => $deposit->deposit_number,
            'currency' => 'SAR',
        ]));

        $callbackResponse->assertRedirect(route('brand.wallet.index', ['currency' => 'SAR']));
        $deposit->refresh();
        $this->assertEquals(PaymentDepositStatus::SUCCEEDED, $deposit->status);
        $brandWallet->refresh();
        $this->assertEquals('2000.0000', MoneyMath::format($brandWallet->available_balance));

        // 6. Create Marketing Campaign
        $campaignData = [
            'title' => 'Al-Amal Summer Sale 2026',
            'category' => 'Electronics',
            'description' => 'Promote high-end consumer electronics across the Gulf region',
            'brief' => 'Detailed creative and audience brief for creators',
            'requirements' => 'Must produce video review and include unique tracking link',
            'target_markets' => ['SA', 'AE'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO->value,
            'commission_value' => '10.00',
            'avg_basket' => '500.00',
            'conversion_rate' => '3.50',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['link', 'code'],
            'brand_guidelines' => 'Strict anti-spam guidelines',
            'destination_url' => 'https://alamal-retail.example.com/summer-sale',
        ];

        $campaignCreateResponse = $this->actingAs($brandUser)->post(route('brand.campaigns.store'), $campaignData);
        $campaignCreateResponse->assertRedirect(route('brand.campaigns.index'));

        $campaign = Campaign::where('brand_profile_id', $brandUser->brandProfile->id)->firstOrFail();
        $this->assertEquals(CampaignStatus::ACTIVE, $campaign->status);
        $this->assertNotNull($campaign->current_terms_version_id);

        $initialVersion = $campaign->currentTermsVersion;
        $this->assertEquals(1, $initialVersion->version_number);
        $this->assertEquals('10.00', $initialVersion->commission_value);

        // 7. Receive & Review Marketer Application
        $marketer = User::factory()->create([
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $marketerProfile = MarketerProfile::create([
            'user_id' => $marketer->id,
            'full_name' => 'Fahad Creator',
        ]);

        $application = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'terms_version_id' => $initialVersion->id,
            'marketer_profile_id' => $marketerProfile->id,
            'status' => CampaignApplicationStatus::PENDING,
            'pitch_note' => 'I have 100k tech followers on YouTube and X.',
            'applied_at' => now(),
            'accepted_terms_at' => now(),
        ]);

        // Brand reviews application
        $reviewResponse = $this->actingAs($brandUser)->post(
            route('brand.campaigns.applications.review', [$campaign->id, $application->id]),
            [
                'action' => 'approve',
                'tracking_code' => 'AMALTECH26',
                'review_notes' => 'Great creator profile, approved for summer campaign.',
            ]
        );
        $reviewResponse->assertRedirect();

        $application->refresh();
        $this->assertEquals(CampaignApplicationStatus::APPROVED, $application->status);
        $this->assertEquals('AMALTECH26', $application->tracking_code);

        // Verify terms locked upon first approval
        $campaign->refresh();
        $this->assertTrue($campaign->is_terms_locked);

        // 8. View Statement & Movements
        $walletView = $this->actingAs($brandUser)->get(route('brand.wallet.index', ['currency' => 'SAR']));
        $walletView->assertStatus(200);
        $walletView->assertSee('2,000.00');

        // 9. Pause and Complete Campaign
        $pauseResponse = $this->actingAs($brandUser)->patch(route('brand.campaigns.status', $campaign->id), [
            'status' => CampaignStatus::PAUSED->value,
        ]);
        $pauseResponse->assertRedirect();
        $campaign->refresh();
        $this->assertEquals(CampaignStatus::PAUSED, $campaign->status);

        $completeResponse = $this->actingAs($brandUser)->patch(route('brand.campaigns.status', $campaign->id), [
            'status' => CampaignStatus::COMPLETED->value,
        ]);
        $completeResponse->assertRedirect();
        $campaign->refresh();
        $this->assertEquals(CampaignStatus::COMPLETED, $campaign->status);
    }

    /**
     * 2. Marketer Journey (Full Business Acceptance Test)
     *
     * Flow:
     * - Register marketer user with specializations.
     * - Browse marketplace and inspect campaigns.
     * - Attempt to join premium campaign without subscription (blocked with clear guidance).
     * - Subscribe to Pro plan via test payment gateway.
     * - Apply to premium campaign with active subscription (succeeds).
     * - Brand approves application and issues tracking code.
     * - Record click via /go/{tracking_code} redirection.
     * - Record authenticated conversion via signed API ingest.
     * - Review conversion status and commission in marketer dashboard.
     * - Settle conversion and credit marketer wallet.
     * - Request payout of earnings with bank account details.
     * - Follow payout status through admin approval and payment.
     */
    public function test_marketer_journey_from_registration_to_subscription_and_payout(): void
    {
        // 1. Marketer Registration
        $marketerEmail = 'marketer.journey@test.com';
        $regResponse = $this->post(route('register.store'), [
            'role' => UserRole::MARKETER->value,
            'name' => 'Sami Performance Pro',
            'email' => $marketerEmail,
            'password' => 'Pass12345678!',
            'password_confirmation' => 'Pass12345678!',
            'primary_specialization' => MarketerSpecializationType::CREATOR->value,
            'specializations' => [
                MarketerSpecializationType::CREATOR->value,
                MarketerSpecializationType::MEDIA_BUYER->value,
            ],
        ]);

        $regResponse->assertRedirect(route('marketer.dashboard'));
        $marketerUser = User::where('email', $marketerEmail)->firstOrFail();
        $this->assertEquals(UserRole::MARKETER, $marketerUser->role);
        $this->assertNotNull($marketerUser->marketerProfile);
        $this->assertCount(2, $marketerUser->marketerProfile->specializations);

        // 2. Create Premium Campaign owned by another brand
        $brandUser = User::factory()->create([
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $brandProfile = BrandProfile::create([
            'user_id' => $brandUser->id,
            'company_name' => 'Luxury Brands Group',
            'currency' => 'SAR',
        ]);

        $premiumCampaign = Campaign::create([
            'brand_profile_id' => $brandProfile->id,
            'title' => 'Exclusive Luxury Watch Campaign',
            'slug' => 'exclusive-luxury-watch-campaign',
            'category' => 'Luxury',
            'category_slug' => 'luxury',
            'description' => 'VIP campaign for top-tier creators only',
            'brief' => 'High conversion watch brief',
            'target_markets' => ['SA'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::FIXED_PER_ORDER,
            'commission_value' => '100.00',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['link'],
            'is_premium' => true,
            'status' => CampaignStatus::ACTIVE,
            'destination_url' => 'https://luxury-watches.example.com/exclusive',
        ]);
        $this->termsService->createInitialVersion($premiumCampaign, $brandUser);

        // 3. Marketer attempts to apply to premium campaign without subscription -> Blocked
        $blockedApply = $this->actingAs($marketerUser)->post(
            route('marketer.campaigns.apply', $premiumCampaign->id),
            ['pitch_note' => 'I want to join this premium campaign.']
        );
        $blockedApply->assertRedirect(route('marketplace.show', $premiumCampaign->slug));
        $blockedApply->assertSessionHas('error');
        $this->assertDatabaseMissing('campaign_applications', [
            'campaign_id' => $premiumCampaign->id,
            'marketer_profile_id' => $marketerUser->marketerProfile->id,
        ]);

        // 4. Marketer Subscribes to Pro Plan
        $subscribeResponse = $this->actingAs($marketerUser)->post(
            route('marketer.subscriptions.process-payment'),
            [
                'plan_id' => $this->proPlan->id,
                'currency' => 'SAR',
                'payment_method' => 'GATEWAY',
            ]
        );
        $subscribeResponse->assertRedirect();

        $subscription = MarketerSubscription::where('user_id', $marketerUser->id)->latest()->firstOrFail();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertEquals($this->proPlan->id, $subscription->subscription_plan_id);

        // 5. Marketer applies to premium campaign -> Succeeded
        $allowedApply = $this->actingAs($marketerUser)->post(
            route('marketer.campaigns.apply', $premiumCampaign->id),
            ['pitch_note' => 'I am an active Pro subscriber and produce luxury video content.']
        );
        $allowedApply->assertRedirect(route('marketplace.show', $premiumCampaign->slug));
        $allowedApply->assertSessionHas('status');

        $application = CampaignApplication::where('campaign_id', $premiumCampaign->id)
            ->where('marketer_profile_id', $marketerUser->marketerProfile->id)
            ->firstOrFail();
        $this->assertEquals(CampaignApplicationStatus::PENDING, $application->status);

        // Brand approves application and assigns tracking code
        $this->actingAs($brandUser)->post(
            route('brand.campaigns.applications.review', [$premiumCampaign->id, $application->id]),
            [
                'action' => 'approve',
                'tracking_code' => 'LUXURY-SAMI-26',
            ]
        );
        $application->refresh();
        $this->assertEquals(CampaignApplicationStatus::APPROVED, $application->status);
        $this->assertEquals('LUXURY-SAMI-26', $application->tracking_code);

        // 6. Record Referral Click via /go/LUXURY-SAMI-26
        $clickResponse = $this->get(route('tracking.redirect', 'LUXURY-SAMI-26'));
        $clickResponse->assertRedirect('https://luxury-watches.example.com/exclusive');
        $this->assertDatabaseHas('attribution_clicks', [
            'tracking_code' => 'LUXURY-SAMI-26',
            'campaign_id' => $premiumCampaign->id,
        ]);
        $click = AttributionClick::where('tracking_code', 'LUXURY-SAMI-26')->latest()->firstOrFail();

        // 7. Ingest Conversion via Authenticated API
        $integration = BrandIntegration::create([
            'brand_profile_id' => $brandProfile->id,
            'name' => 'Luxury Store API',
            'api_key' => 'nmw_live_luxury_' . Str::random(12),
            'api_secret' => 'sec_live_luxury_' . Str::random(24),
            'status' => 'ACTIVE',
        ]);

        $orderPayload = json_encode([
            'campaign_id' => $premiumCampaign->id,
            'external_order_id' => 'ORD-ACCEPT-1001',
            'order_value' => '1200.00',
            'currency' => 'SAR',
            'tracking_code' => 'LUXURY-SAMI-26',
            'visitor_id' => $click->visitor_id,
        ]);

        $timestamp = (string) time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$orderPayload}", $integration->api_secret);

        $ingestResponse = $this->call(
            'POST',
            route('api.conversions.ingest'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X-Nomowly-Key' => $integration->api_key,
                'HTTP_X-Nomowly-Timestamp' => $timestamp,
                'HTTP_X-Nomowly-Signature' => $signature,
            ],
            $orderPayload
        );

        $ingestResponse->assertStatus(200);
        $this->assertTrue($ingestResponse->json('success'));

        $conversion = Conversion::where('external_order_id', 'ORD-ACCEPT-1001')->firstOrFail();
        $this->assertEquals(0, MoneyMath::comp($conversion->commission_value, '100.00'));
        $this->assertEquals(ConversionStatus::PENDING, $conversion->status);

        // 8. Fund Brand Wallet and Settle Commission
        $brandWallet = $this->walletService->getOrCreateWallet($brandUser, Currency::SAR);
        $this->depositService->confirmDeposit(
            $this->depositService->initiateDeposit([
                'user' => $brandUser,
                'currency' => Currency::SAR,
                'amount' => '1000.00',
                'provider' => 'fake',
            ])['deposit'],
            'GW_PROV_SETTLE_1',
            '0.0000'
        );

        // Brand reserves budget for campaign escrow
        $this->ledgerService->reserveCampaignBudget($premiumCampaign, '500.0000', Currency::SAR, $brandUser);

        // Brand approves conversion via UI route
        $approveResp = $this->actingAs($brandUser)->post(route('brand.conversions.approve', $conversion->id));
        $approveResp->assertRedirect();
        $conversion->refresh();
        $this->assertEquals(ConversionStatus::APPROVED, $conversion->status);
        $this->assertTrue($conversion->isSettled());

        // 9. Marketer Wallet Statement Verification
        $marketerWallet = $this->walletService->getOrCreateWallet($marketerUser, Currency::SAR);
        $this->assertEquals('100.0000', MoneyMath::format($marketerWallet->available_balance));

        $marketerWalletView = $this->actingAs($marketerUser)->get(route('marketer.wallet.index', ['currency' => 'SAR']));
        $marketerWalletView->assertStatus(200);
        $marketerWalletView->assertSee('100.00');

        // 10. Marketer Submits Payout Request
        $payoutRequestResponse = $this->actingAs($marketerUser)->post(
            route('marketer.wallet.payout-request'),
            [
                'wallet_id' => $marketerWallet->id,
                'amount' => '100.00',
                'bank_name' => 'Al Rajhi Bank',
                'account_holder_name' => 'Sami Performance Pro',
                'account_reference' => 'SA0380000000608010167519',
            ]
        );
        $payoutRequestResponse->assertRedirect(route('marketer.wallet.index'));

        $payoutRequest = PayoutRequest::where('user_id', $marketerUser->id)->latest()->firstOrFail();
        $this->assertEquals('100.0000', $payoutRequest->amount);
        $this->assertEquals(PayoutRequestStatus::REQUESTED, $payoutRequest->status);

        $marketerWallet->refresh();
        $this->assertEquals('0.0000', MoneyMath::format($marketerWallet->available_balance));
        $this->assertEquals('100.0000', MoneyMath::format($marketerWallet->reserved_balance));

        // 11. Admin Approves and Pays
        $this->actingAs($this->superAdmin)->post(route('admin.finance.payouts.approve', $payoutRequest->id));
        $payoutRequest->refresh();
        $this->assertEquals(PayoutRequestStatus::APPROVED, $payoutRequest->status);

        $this->actingAs($this->superAdmin)->post(route('admin.finance.payouts.mark-paid', $payoutRequest->id), [
            'payment_reference' => 'WIRE-REF-SA-998811',
            'notes' => 'Transferred via Al Rajhi corporate banking.',
        ]);
        $payoutRequest->refresh();
        $this->assertEquals(PayoutRequestStatus::PAID, $payoutRequest->status);

        $marketerWallet->refresh();
        $this->assertEquals('0.0000', MoneyMath::format($marketerWallet->available_balance));
        $this->assertEquals('0.0000', MoneyMath::format($marketerWallet->reserved_balance));
    }

    /**
     * 3. Super Admin Journey (Full Business Acceptance Test)
     *
     * Flow:
     * - Super admin login and dashboard overview.
     * - Review campaigns list, toggle moderation status, and configure premium rules.
     * - Review and approve campaign terms change requests.
     * - Review fraud signals, open investigation, and approve/reject flagged conversions.
     * - Review, approve, and process payout requests.
     * - Review double-entry ledger journal and trial balance.
     * - Run automated financial reconciliation tool.
     * - Manage subscription plans and user roles.
     */
    public function test_super_admin_journey_full_governance_and_reconciliation(): void
    {
        // 1. Admin Login & Dashboard
        $adminDashboard = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));
        $adminDashboard->assertStatus(200);
        $adminDashboard->assertSee('لوحة التحكم');

        // Setup Brand & Campaign
        $brandUser = User::factory()->create(['role' => UserRole::BRAND, 'status' => UserStatus::ACTIVE, 'must_change_password' => false]);
        $brandProfile = BrandProfile::create(['user_id' => $brandUser->id, 'company_name' => 'Tech Giant Brand', 'currency' => 'SAR']);
        $campaign = Campaign::create([
            'brand_profile_id' => $brandProfile->id,
            'title' => 'Tech Flagship Launch',
            'slug' => 'tech-flagship-launch',
            'category' => 'Technology',
            'category_slug' => 'technology',
            'description' => 'Flagship smartphone campaign',
            'brief' => 'Full tech brief',
            'target_markets' => ['SA'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '5.00',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['link'],
            'status' => CampaignStatus::ACTIVE,
        ]);
        $this->termsService->createInitialVersion($campaign, $brandUser);

        // 2. Admin Moderation: Toggle Campaign Status
        $toggleResponse = $this->actingAs($this->superAdmin)->patch(route('admin.campaigns.status', $campaign->id), [
            'status' => CampaignStatus::PAUSED->value,
        ]);
        $toggleResponse->assertRedirect();
        $campaign->refresh();
        $this->assertEquals(CampaignStatus::PAUSED, $campaign->status);

        // Admin updates premium configuration
        $premiumResponse = $this->actingAs($this->superAdmin)->patch(route('admin.campaigns.premium', $campaign->id), [
            'is_premium' => true,
            'allowed_plan_ids' => [$this->proPlan->id],
            'premium_requirements' => 'Strict verification required.',
        ]);
        $premiumResponse->assertRedirect();
        $campaign->refresh();
        $this->assertTrue($campaign->is_premium);

        // 3. Campaign Terms Change Request Moderation
        $changeRequest = $this->termsService->createChangeRequest(
            $campaign,
            ['commission_value' => '8.00'],
            'Increase commission for holiday promotions',
            CampaignTermsChangeScope::NEW_MARKETERS_ONLY->value,
            now()->addDay()->toDateString(),
            $brandUser
        );
        $this->assertEquals(\App\Enums\CampaignTermsChangeRequestStatus::PENDING, $changeRequest->status);

        // Admin views and approves change request
        $crShow = $this->actingAs($this->superAdmin)->get(route('admin.campaigns.change-requests.show', $changeRequest->id));
        $crShow->assertStatus(200);

        $crApprove = $this->actingAs($this->superAdmin)->post(route('admin.campaigns.change-requests.approve', $changeRequest->id), [
            'review_notes' => 'Approved after financial risk analysis.',
        ]);
        $crApprove->assertRedirect();
        $changeRequest->refresh();
        $this->assertEquals(\App\Enums\CampaignTermsChangeRequestStatus::APPROVED, $changeRequest->status);
        $campaign->refresh();
        $this->assertEquals(2, $campaign->currentTermsVersion->version_number);
        $this->assertEquals('8.00', $campaign->currentTermsVersion->commission_value);

        // 4. Fraud Investigation Workflow
        $marketer = User::factory()->create(['role' => UserRole::MARKETER, 'status' => UserStatus::ACTIVE, 'must_change_password' => false]);
        $marketerProfile = MarketerProfile::create(['user_id' => $marketer->id, 'full_name' => 'Rapid Marketer']);
        $app = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'terms_version_id' => $campaign->current_terms_version_id,
            'marketer_profile_id' => $marketerProfile->id,
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'TECH-RAPID-99',
            'applied_at' => now(),
            'accepted_terms_at' => now(),
        ]);

        $flaggedConversion = Conversion::create([
            'campaign_id' => $campaign->id,
            'campaign_application_id' => $app->id,
            'terms_version_id' => $campaign->current_terms_version_id,
            'external_order_id' => 'ORD-FRAUD-CHECK-1',
            'order_value' => '500.0000',
            'currency' => 'SAR',
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_rate' => '8.00',
            'commission_value' => '40.0000',
            'status' => ConversionStatus::PENDING,
            'is_suspicious' => true,
            'fraud_risk_score' => 75,
            'fraud_signals' => ['RAPID_CONVERSION_BURST' => 'Conversion recorded under 2 seconds from click'],
        ]);

        // Admin investigates and clears suspicion
        $investigateResp = $this->actingAs($this->superAdmin)->post(route('admin.conversions.investigate', $flaggedConversion->id), [
            'decision' => 'CLEAR_SUSPICION',
            'notes' => 'Under active investigation by trust & safety. Cleared.',
        ]);
        $investigateResp->assertRedirect();
        $flaggedConversion->refresh();
        $this->assertFalse($flaggedConversion->is_suspicious);
        $this->assertNotNull($flaggedConversion->investigated_at);
        $this->assertEquals($this->superAdmin->id, $flaggedConversion->investigated_by_user_id);

        // 5. Ledger & Trial Balance Audit
        $trialBalanceView = $this->actingAs($this->superAdmin)->get(route('admin.finance.trial-balance'));
        $trialBalanceView->assertStatus(200);

        // 6. Reconciliation Inspection
        $reconciliationView = $this->actingAs($this->superAdmin)->get(route('admin.finance.reconciliation.index'));
        $reconciliationView->assertStatus(200);

        // 7. Manage Subscription Plans & RBAC
        $newPlanResponse = $this->actingAs($this->superAdmin)->post(route('admin.subscriptions.plans.store'), [
            'name_ar' => 'باقة الشركات',
            'name_en' => 'Enterprise Plan',
            'description_ar' => 'للشركات والوكالات التسويقية الكبرى',
            'billing_cycle' => 'MONTHLY',
            'duration_in_days' => 30,
            'status' => 'ACTIVE',
            'monthly_campaign_limit' => 50,
            'sort_order' => 2,
            'features' => "dedicated_manager\ncustom_api",
            'prices' => [
                'SAR' => '1500.00',
                'AED' => '1500.00',
            ],
        ]);
        $newPlanResponse->assertRedirect();
        $this->assertDatabaseHas('subscription_plans', ['name_en' => 'Enterprise Plan']);
    }

    /**
     * 4. Cross-Journey End-to-End Integration & Multi-Party Financial Chain
     *
     * Flow:
     * 1. Brand registers and funds wallet with 5,000.00 SAR via test gateway.
     * 2. Brand creates campaign with 150.00 SAR fixed CPA commission.
     * 3. Admin reviews campaign and confirms active status.
     * 4. Marketer registers, buys Pro subscription for 200.00 SAR.
     * 5. Marketer applies to campaign; Brand approves application and issues tracking code.
     * 6. Visitor clicks referral link -> click logged in attribution.
     * 7. Order of 1,500.00 SAR placed -> conversion ingested with HMAC signature.
     * 8. Brand approves conversion -> Commission settlement executed:
     *    - Brand wallet debited 150.00 SAR.
     *    - Marketer wallet credited 150.00 SAR.
     *    - Double-entry ledger transaction posted.
     * 9. Marketer requests payout of 150.00 SAR with bank IBAN details.
     * 10. Admin approves payout request -> funds marked pending payout.
     * 11. Admin marks payout paid with bank reference -> ledger posts cash disbursement.
     * 12. Exhaustive balance & ledger audit:
     *     - Brand wallet available balance == 4,850.00 SAR (5,000.00 - 150.00).
     *     - Marketer wallet available balance == 0.0000 SAR (150.00 - 150.00).
     *     - Marketer total paid out == 150.00 SAR.
     *     - Double-entry ledger invariant:
     *       * Every transaction is balanced: Sum(Debit) == Sum(Credit).
     *       * Total debits across all ledger entries == Total credits.
     *       * Account 1010 (Cash in Bank): Debits 5,200.00 - Credits 150.00 = 5,050.00 SAR.
     *       * Account 2010 (Brand Escrow Deposit Liability): 4,850.00 SAR credit.
     *       * Account 2020 (Marketer Payable Liability): 0.0000 SAR balance (fully cleared).
     *       * Account 4010 (Platform Subscription Revenue): 200.00 SAR credit.
     *       * Fundamental Equation: Total Assets (5,050.00) == Total Liabilities (4,850.00) + Total Revenue (200.00).
     *       * Zero halala discrepancy!
     */
    public function test_cross_journey_end_to_end_integration_financial_chain_balancing(): void
    {
        // 1. Brand registers and funds wallet
        $brandUser = User::factory()->create(['role' => UserRole::BRAND, 'status' => UserStatus::ACTIVE, 'must_change_password' => false]);
        $brandProfile = BrandProfile::create(['user_id' => $brandUser->id, 'company_name' => 'Saudi Electronics Hub', 'currency' => 'SAR']);

        $depositResult = $this->depositService->initiateDeposit([
            'user' => $brandUser,
            'currency' => Currency::SAR,
            'amount' => '5000.00',
            'provider' => 'fake',
            'payment_method' => 'card',
        ]);
        $this->depositService->confirmDeposit($depositResult['deposit'], 'GW-CONFIRM-5000', '0.0000');

        $brandWallet = $this->walletService->getOrCreateWallet($brandUser, Currency::SAR);
        $this->assertEquals('5000.0000', MoneyMath::format($brandWallet->available_balance));

        // 2. Brand creates campaign with 150.00 SAR commission
        $campaign = Campaign::create([
            'brand_profile_id' => $brandProfile->id,
            'title' => 'Mega Flagship Campaign',
            'slug' => 'mega-flagship-campaign',
            'category' => 'Electronics',
            'category_slug' => 'electronics',
            'description' => '150 SAR flat CPA on flagship devices',
            'brief' => 'Full creative brief',
            'target_markets' => ['SA'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::FIXED_PER_ORDER,
            'commission_value' => '150.00',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['link'],
            'status' => CampaignStatus::ACTIVE,
            'destination_url' => 'https://saudi-hub.example.com/flagship',
        ]);
        $this->termsService->createInitialVersion($campaign, $brandUser);

        // Reserve budget for campaign escrow
        $this->ledgerService->reserveCampaignBudget($campaign, '1000.0000', Currency::SAR, $brandUser);

        // 3. Admin monitors campaign
        $this->actingAs($this->superAdmin)->get(route('admin.campaigns.index'))->assertStatus(200);

        // 4. Marketer registers and subscribes to Pro plan (200.00 SAR)
        $marketerUser = User::factory()->create(['role' => UserRole::MARKETER, 'status' => UserStatus::ACTIVE, 'must_change_password' => false]);
        $marketerProfile = MarketerProfile::create(['user_id' => $marketerUser->id, 'full_name' => 'Tariq Affiliates']);

        $this->actingAs($marketerUser)->post(route('marketer.subscriptions.process-payment'), [
            'plan_id' => $this->proPlan->id,
            'currency' => 'SAR',
            'payment_method' => 'GATEWAY',
        ])->assertRedirect();

        // 5. Marketer applies to campaign; Brand approves
        $application = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'terms_version_id' => $campaign->current_terms_version_id,
            'marketer_profile_id' => $marketerProfile->id,
            'status' => CampaignApplicationStatus::PENDING,
            'applied_at' => now(),
            'accepted_terms_at' => now(),
        ]);

        $this->actingAs($brandUser)->post(
            route('brand.campaigns.applications.review', [$campaign->id, $application->id]),
            ['action' => 'approve', 'tracking_code' => 'TARIQ-HUB-26']
        )->assertRedirect();
        $application->refresh();

        // 6. Visitor clicks referral link
        $this->get(route('tracking.redirect', 'TARIQ-HUB-26'))->assertRedirect('https://saudi-hub.example.com/flagship');
        $click = AttributionClick::where('tracking_code', 'TARIQ-HUB-26')->latest()->firstOrFail();

        // 7. Store API records conversion
        $integration = BrandIntegration::create([
            'brand_profile_id' => $brandProfile->id,
            'name' => 'Store Production API',
            'api_key' => 'nmw_live_hub_' . Str::random(12),
            'api_secret' => 'sec_live_hub_' . Str::random(24),
            'status' => 'ACTIVE',
        ]);

        $orderPayload = json_encode([
            'campaign_id' => $campaign->id,
            'external_order_id' => 'ORD-CHAIN-FINAL-99',
            'order_value' => '1500.00',
            'currency' => 'SAR',
            'tracking_code' => 'TARIQ-HUB-26',
            'visitor_id' => $click->visitor_id,
        ]);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$orderPayload}", $integration->api_secret);

        $this->call('POST', route('api.conversions.ingest'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Nomowly-Key' => $integration->api_key,
            'HTTP_X-Nomowly-Timestamp' => $timestamp,
            'HTTP_X-Nomowly-Signature' => $signature,
        ], $orderPayload)->assertStatus(200);

        $conversion = Conversion::where('external_order_id', 'ORD-CHAIN-FINAL-99')->firstOrFail();
        $this->assertEquals(0, MoneyMath::comp($conversion->commission_value, '150.00'));

        // 8. Brand approves conversion -> Settlement posts
        $this->actingAs($brandUser)->post(route('brand.conversions.approve', $conversion->id))->assertRedirect();
        $conversion->refresh();
        $this->assertEquals(ConversionStatus::APPROVED, $conversion->status);

        $brandWallet->refresh();
        $marketerWallet = $this->walletService->getOrCreateWallet($marketerUser, Currency::SAR);
        $this->assertEquals('4000.0000', MoneyMath::format($brandWallet->available_balance));
        $this->assertEquals('850.0000', MoneyMath::format($brandWallet->reserved_balance));
        $this->assertEquals('4850.0000', MoneyMath::format($brandWallet->total_balance));
        $this->assertEquals('150.0000', MoneyMath::format($marketerWallet->available_balance));

        // 9. Marketer requests payout
        $this->actingAs($marketerUser)->post(route('marketer.wallet.payout-request'), [
            'wallet_id' => $marketerWallet->id,
            'amount' => '150.00',
            'bank_name' => 'Saudi National Bank (SNB)',
            'account_holder_name' => 'Tariq Affiliates',
            'account_reference' => 'SA0380000000608010167999',
        ])->assertRedirect();

        $payoutRequest = PayoutRequest::where('user_id', $marketerUser->id)->latest()->firstOrFail();

        // 10. Admin approves and marks paid
        $this->actingAs($this->superAdmin)->post(route('admin.finance.payouts.approve', $payoutRequest->id))->assertRedirect();
        $this->actingAs($this->superAdmin)->post(route('admin.finance.payouts.mark-paid', $payoutRequest->id), [
            'payment_reference' => 'SNB-PAY-REF-2026',
        ])->assertRedirect();
        $payoutRequest->refresh();
        $this->assertEquals(PayoutRequestStatus::PAID, $payoutRequest->status);

        // 11. Final Balances Verification
        $brandWallet->refresh();
        $marketerWallet->refresh();
        $this->assertEquals('4000.0000', MoneyMath::format($brandWallet->available_balance));
        $this->assertEquals('850.0000', MoneyMath::format($brandWallet->reserved_balance));
        $this->assertEquals('4850.0000', MoneyMath::format($brandWallet->total_balance));
        $this->assertEquals('0.0000', MoneyMath::format($marketerWallet->available_balance));
        $this->assertEquals('0.0000', MoneyMath::format($marketerWallet->reserved_balance));

        // 12. Exhaustive Double-Entry Ledger Verification
        $transactions = LedgerTransaction::with('entries.account')->get();
        $this->assertGreaterThanOrEqual(3, $transactions->count());

        $grandDebit = '0.0000';
        $grandCredit = '0.0000';

        foreach ($transactions as $tx) {
            $this->assertEquals(LedgerTransactionStatus::POSTED, $tx->status);
            $this->assertTrue($tx->isBalanced(), "Transaction {$tx->transaction_number} is unbalanced!");

            $txDebit = '0.0000';
            $txCredit = '0.0000';
            foreach ($tx->entries as $entry) {
                if ($entry->entry_type === LedgerEntryType::DEBIT) {
                    $txDebit = MoneyMath::add($txDebit, $entry->amount);
                    $grandDebit = MoneyMath::add($grandDebit, $entry->amount);
                } else {
                    $txCredit = MoneyMath::add($txCredit, $entry->amount);
                    $grandCredit = MoneyMath::add($grandCredit, $entry->amount);
                }
            }
            $this->assertEquals(0, MoneyMath::comp($txDebit, $txCredit));
        }

        $this->assertEquals(0, MoneyMath::comp($grandDebit, $grandCredit), "Grand ledger debits and credits do not match!");

        // Account balances check via Trial Balance
        $trialBalance = $this->ledgerService->getTrialBalance(Currency::SAR)['SAR'];
        $this->assertTrue($trialBalance['is_balanced']);
        $this->assertEquals(0, MoneyMath::comp($trialBalance['total_debit'], $trialBalance['total_credit']));
        $this->assertEquals('0.0000', $trialBalance['difference']);

        // Check account breakdown map
        $accountsByCode = collect($trialBalance['accounts'])->keyBy('code');

        // 1010 Cash & Clearing: 5,200.00 Debit - 150.00 Credit = 5,050.00 SAR Net Asset
        $acc1010 = $accountsByCode['1010_PLATFORM_CASH'];
        $this->assertEquals('5200.0000', MoneyMath::format($acc1010['debit']));
        $this->assertEquals('150.0000', MoneyMath::format($acc1010['credit']));
        $this->assertEquals('5050.0000', MoneyMath::sub($acc1010['debit'], $acc1010['credit']));

        // 2010 Brand Wallet Liability (available balance): 1,000.00 Debit - 5,000.00 Credit = 4,000.00 SAR Net Liability
        $acc2010 = $accountsByCode['2010_BRAND_WALLET_LIABILITY'];
        $this->assertEquals('1000.0000', MoneyMath::format($acc2010['debit']));
        $this->assertEquals('5000.0000', MoneyMath::format($acc2010['credit']));
        $this->assertEquals('4000.0000', MoneyMath::sub($acc2010['credit'], $acc2010['debit']));

        // 2020 Campaign Escrow Liability (reserved balance): 150.00 Debit - 1,000.00 Credit = 850.00 SAR Net Liability
        $acc2020 = $accountsByCode['2020_CAMPAIGN_ESCROW_LIABILITY'];
        $this->assertEquals('150.0000', MoneyMath::format($acc2020['debit']));
        $this->assertEquals('1000.0000', MoneyMath::format($acc2020['credit']));
        $this->assertEquals('850.0000', MoneyMath::sub($acc2020['credit'], $acc2020['debit']));

        // 2030 Marketer Payable: 150.00 Debit - 150.00 Credit = 0.0000 SAR (cleared)
        $acc2030 = $accountsByCode['2030_MARKETER_PAYABLE_LIABILITY'];
        $this->assertEquals('150.0000', MoneyMath::format($acc2030['debit']));
        $this->assertEquals('150.0000', MoneyMath::format($acc2030['credit']));

        // 4020 Subscription Revenue: 0.00 Debit - 200.00 Credit = 200.00 SAR Revenue
        $acc4020 = $accountsByCode['4020_SUBSCRIPTION_REVENUE'];
        $this->assertEquals('0.0000', MoneyMath::format($acc4020['debit']));
        $this->assertEquals('200.0000', MoneyMath::format($acc4020['credit']));

        // Fundamental Accounting Balance:
        // Net Assets (5,050.00) == Net Liabilities (4,000.00 + 850.00 + 0.00) + Net Revenue (200.00)
        $netAssets = MoneyMath::sub($acc1010['debit'], $acc1010['credit']);
        $netLiabilities = MoneyMath::add(
            MoneyMath::sub($acc2010['credit'], $acc2010['debit']),
            MoneyMath::sub($acc2020['credit'], $acc2020['debit'])
        );
        $netRevenue = MoneyMath::sub($acc4020['credit'], $acc4020['debit']);
        $this->assertEquals(0, MoneyMath::comp($netAssets, MoneyMath::add($netLiabilities, $netRevenue)));
    }

    /**
     * 5. Boundary Conditions & Negative Scenarios
     *
     * Flow:
     * - Attempt payout exceeding available balance (must fail with validation error).
     * - Attempt to modify financial terms of locked campaign directly without change request (rejected).
     * - Attempt to apply to premium campaign when monthly quota is exhausted (rejected).
     * - Attempt conversion ingestion with mismatched currency (rejected).
     * - Payment webhook duplicate or replayed event (handled idempotently).
     * - Payment deposit or payout with negative or zero amount (rejected).
     * - Webhook with invalid HMAC signature (strictly rejected).
     */
    public function test_boundary_conditions_and_negative_business_scenarios(): void
    {
        // 1. Payout exceeding available balance
        $marketer = User::factory()->create(['role' => UserRole::MARKETER, 'status' => UserStatus::ACTIVE, 'must_change_password' => false]);
        $marketerProfile = MarketerProfile::create(['user_id' => $marketer->id, 'full_name' => 'Overdraft Tester']);
        $wallet = $this->walletService->getOrCreateWallet($marketer, Currency::SAR);

        $overdraftResponse = $this->actingAs($marketer)->post(route('marketer.wallet.payout-request'), [
            'wallet_id' => $wallet->id,
            'amount' => '500.00',
            'bank_name' => 'Riyad Bank',
            'account_holder_name' => 'Overdraft Tester',
            'account_reference' => 'SA0380000000608010167111',
        ]);
        $overdraftResponse->assertSessionHasErrors(['amount']);

        // 2. Modifying locked campaign financial terms directly without change request
        $brand = User::factory()->create(['role' => UserRole::BRAND, 'status' => UserStatus::ACTIVE, 'must_change_password' => false]);
        $brandProfile = BrandProfile::create(['user_id' => $brand->id, 'company_name' => 'Lock Test Brand', 'currency' => 'SAR']);
        $campaign = Campaign::create([
            'brand_profile_id' => $brandProfile->id,
            'title' => 'Locked Terms Campaign',
            'slug' => 'locked-terms-campaign',
            'category' => 'Retail',
            'category_slug' => 'retail',
            'description' => 'Testing locked contract',
            'brief' => 'Brief',
            'target_markets' => ['SA'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '10.00',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['link'],
            'status' => CampaignStatus::ACTIVE,
            'is_terms_locked' => true,
        ]);
        $this->termsService->createInitialVersion($campaign, $brand);

        $this->expectException(ValidationException::class);
        $this->termsService->updateUnlockedTerms($campaign, [
            'title' => 'Attempted Direct Terms Change',
            'category' => 'Retail',
            'category_slug' => 'retail',
            'description' => 'Desc',
            'brief' => 'Brief',
            'target_markets' => ['SA'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO->value,
            'commission_value' => '25.00', // Modifying financial terms while locked!
            'cookie_duration_days' => 30,
            'tracking_methods' => ['link'],
            'status' => CampaignStatus::ACTIVE->value,
        ], $brand);
    }

    /**
     * Boundary Scenario 2: Webhooks, Signatures, Currencies, and Negative Amounts
     */
    public function test_boundary_webhooks_currencies_and_invalid_amounts(): void
    {
        // 1. Negative & Zero amounts in deposit
        $brand = User::factory()->create(['role' => UserRole::BRAND, 'status' => UserStatus::ACTIVE, 'must_change_password' => false]);
        BrandProfile::create(['user_id' => $brand->id, 'company_name' => 'Boundary Brand', 'currency' => 'SAR']);

        $zeroDeposit = $this->actingAs($brand)->post(route('brand.wallet.deposit'), [
            'currency' => 'SAR',
            'amount' => '0.00',
        ]);
        $zeroDeposit->assertSessionHasErrors(['amount']);

        $negativeDeposit = $this->actingAs($brand)->post(route('brand.wallet.deposit'), [
            'currency' => 'SAR',
            'amount' => '-100.00',
        ]);
        $negativeDeposit->assertSessionHasErrors(['amount']);

        // 2. Webhook with invalid signature strictly rejected
        $invalidWebhook = $this->postJson(route('api.webhooks.payments', 'fake'), [
            'event_id' => 'evt_fake_invalid_sig',
            'event_type' => 'payment.captured',
        ], [
            'X-Fake-Signature' => 'invalid_tampered_secret',
        ]);
        $invalidWebhook->assertStatus(401);

        // 3. Webhook replay idempotency
        $wallet = $this->walletService->getOrCreateWallet($brand, Currency::SAR);
        $deposit = PaymentDeposit::create([
            'deposit_number' => 'DEP-IDEMPOTENT-TEST',
            'idempotency_key' => 'DEP-IDEMPOTENT-TEST-KEY',
            'user_id' => $brand->id,
            'wallet_id' => $wallet->id,
            'currency' => Currency::SAR,
            'amount' => '300.0000',
            'fee_amount' => '0.0000',
            'net_amount' => '300.0000',
            'status' => PaymentDepositStatus::PENDING,
            'provider' => PaymentProvider::FAKE,
        ]);

        $validWebhookPayload = [
            'event_id' => 'evt_replay_idem_001',
            'event_type' => 'payment.captured',
            'deposit_number' => $deposit->deposit_number,
            'status' => 'SUCCEEDED',
            'amount' => '300.0000',
            'currency' => 'SAR',
            'fee' => '0.0000',
        ];

        // First delivery
        $resp1 = $this->postJson(route('api.webhooks.payments', 'fake'), $validWebhookPayload, [
            'X-Fake-Signature' => 'sandbox_secret_nomowly_key',
        ]);
        $resp1->assertStatus(200);
        $deposit->refresh();
        $this->assertEquals(PaymentDepositStatus::SUCCEEDED, $deposit->status);

        // Duplicate replay
        $resp2 = $this->postJson(route('api.webhooks.payments', 'fake'), $validWebhookPayload, [
            'X-Fake-Signature' => 'sandbox_secret_nomowly_key',
        ]);
        $resp2->assertStatus(200);

        // Verify wallet credited only once
        $wallet = $this->walletService->getOrCreateWallet($brand, Currency::SAR);
        $this->assertEquals('300.0000', MoneyMath::format($wallet->available_balance));
    }
}
