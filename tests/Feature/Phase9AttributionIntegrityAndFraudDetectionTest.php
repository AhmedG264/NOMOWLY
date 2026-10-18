<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\CampaignApplicationStatus;
use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Enums\ConversionStatus;
use App\Enums\Currency;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AttributionClick;
use App\Models\AuditLog;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\Conversion;
use App\Models\FraudDetectionRule;
use App\Models\MarketerProfile;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AttributionService;
use App\Services\Fraud\FraudDetectionService;
use App\Services\Ledger\CommissionSettlementService;
use App\Services\Ledger\WalletService;
use App\Services\Math\MoneyMath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase9AttributionIntegrityAndFraudDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $brandUser;
    protected BrandProfile $brandProfile;
    protected User $otherBrandUser;
    protected BrandProfile $otherBrandProfile;
    protected User $marketerUser;
    protected MarketerProfile $marketerProfile;
    protected Campaign $campaign;
    protected CampaignApplication $application;
    protected AttributionService $attributionService;
    protected FraudDetectionService $fraudService;
    protected CommissionSettlementService $settlementService;
    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attributionService = app(AttributionService::class);
        $this->fraudService = app(FraudDetectionService::class);
        $this->settlementService = app(CommissionSettlementService::class);
        $this->walletService = app(WalletService::class);

        // 1. Super Admin
        $this->superAdmin = User::create([
            'name' => 'Super Admin Officer',
            'email' => 'superadmin@nomowly.com',
            'password' => bcrypt('StrongPassword123!'),
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        // 2. Brand User & Profile
        $this->brandUser = User::create([
            'name' => 'Al-Riyadh Tech Brand',
            'email' => 'brand@riyadhtech.com',
            'password' => bcrypt('StrongPassword123!'),
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->brandProfile = BrandProfile::create([
            'user_id' => $this->brandUser->id,
            'company_name' => 'Al-Riyadh Tech',
            'legal_name' => 'Al-Riyadh Tech Co',
            'commercial_reg_no' => '1010998877',
            'website_url' => 'https://riyadhtech.com',
            'currency' => 'SAR',
        ]);

        // 3. Other Brand User (For tenancy isolation tests)
        $this->otherBrandUser = User::create([
            'name' => 'Jeddah Fashion Brand',
            'email' => 'brand@jeddahfashion.com',
            'password' => bcrypt('StrongPassword123!'),
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->otherBrandProfile = BrandProfile::create([
            'user_id' => $this->otherBrandUser->id,
            'company_name' => 'Jeddah Fashion',
            'legal_name' => 'Jeddah Fashion LLC',
            'commercial_reg_no' => '4030112233',
            'website_url' => 'https://jeddahfashion.com',
            'currency' => 'SAR',
        ]);

        // 4. Marketer User & Profile
        $this->marketerUser = User::create([
            'name' => 'Zaid Marketer',
            'email' => 'zaid@nomowlymarketers.com',
            'password' => bcrypt('StrongPassword123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->marketerProfile = MarketerProfile::create([
            'user_id' => $this->marketerUser->id,
            'full_name' => 'Zaid Al-Harbi',
            'country' => 'SA',
            'preferred_currency' => 'SAR',
        ]);

        // 5. Active Campaign with Percentage Commission
        $this->campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfile->id,
            'title' => 'حملة الإلكترونيات الكبرى 2026',
            'slug' => 'tech-sale-2026',
            'destination_url' => 'https://riyadhtech.com/offers',
            'category' => 'إلكترونيات وأجهزة',
            'category_slug' => 'electronics',
            'description' => 'حملة تسويق أداء للأجهزة الذكية والإلكترونيات.',
            'brief' => 'شروط الحملة ونسب العمولات.',
            'requirements' => 'الترويج عبر قنوات التقنية.',
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'tracking_methods' => ['رابط تتبع ذكي', 'كود خصم حصري'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '10.00', // 10%
            'cookie_duration_days' => 30,
            'status' => CampaignStatus::ACTIVE,
            'current_terms_version_id' => null,
            'terms_locked' => false,
        ]);

        // Automatically create terms v1 using CampaignTermsService
        $termsV1 = app(\App\Services\Campaign\CampaignTermsService::class)->createInitialVersion($this->campaign, $this->brandUser);

        // 6. Approved Marketer Application
        $this->application = CampaignApplication::create([
            'campaign_id' => $this->campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'terms_version_id' => $termsV1->id,
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'ZAIDTECH2026',
            'tracking_link' => 'http://localhost/go/ZAIDTECH2026',
            'reviewed_at' => now(),
            'reviewed_by' => $this->brandUser->id,
        ]);

        // Ensure default fraud detection rules exist
        if (FraudDetectionRule::count() === 0) {
            FraudDetectionRule::create([
                'rule_key' => 'click_burst_limit',
                'name_ar' => 'كثافة نقرات غير طبيعية خلال دقيقة واحدة',
                'description' => 'تدفق نقرات كثيف من نفس البصمة التقنية في 60 ثانية.',
                'is_active' => true,
                'threshold_value' => '15',
                'risk_weight' => 35,
                'action' => 'FLAG_SUSPICIOUS',
            ]);
            FraudDetectionRule::create([
                'rule_key' => 'rapid_conversion_seconds',
                'name_ar' => 'سرعة تحويل فورية غير بشرية بعد النقرة',
                'description' => 'تحويل خلال أقل من 3 ثوان من النقرة.',
                'is_active' => true,
                'threshold_value' => '3',
                'risk_weight' => 45,
                'action' => 'FLAG_SUSPICIOUS',
            ]);
            FraudDetectionRule::create([
                'rule_key' => 'conversion_velocity_window',
                'name_ar' => 'تكرار تحويلات متتالية لنفس الزائر في نافذة وجيزة',
                'description' => 'أكثر من تحويل في 120 ثانية.',
                'is_active' => true,
                'threshold_value' => '120',
                'risk_weight' => 30,
                'action' => 'FLAG_SUSPICIOUS',
            ]);
            FraudDetectionRule::create([
                'rule_key' => 'high_order_value_multiplier',
                'name_ar' => 'مضاعف قيمة طلب شاذ مقارنة بمتوسط السلة',
                'description' => 'طلب يتجاوز 10 أضعاف المتوسط.',
                'is_active' => true,
                'threshold_value' => '10',
                'risk_weight' => 25,
                'action' => 'FLAG_SUSPICIOUS',
            ]);
        }
    }

    /**
     * 1. Test accurate last-touch attribution to rightful marketer
     */
    public function test_accurate_last_touch_attribution_to_rightful_marketer(): void
    {
        $click = AttributionClick::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'terms_version_id' => $this->application->terms_version_id,
            'tracking_code' => $this->application->tracking_code,
            'visitor_id' => 'vid_valid_customer_123',
            'session_id' => 'sid_abc_456',
            'ip_hash' => hash('sha256', '127.0.0.1_salt'),
            'user_agent_hash' => hash('sha256', 'Mozilla/5.0 Chrome/120'),
            'country' => 'SA',
            'device_type' => 'desktop',
            'created_at' => now()->subMinutes(10),
        ]);

        $conversion = $this->attributionService->recordConversion([
            'campaign' => $this->campaign,
            'external_order_id' => 'ORD-RIYADH-001',
            'order_value' => 500.00,
            'currency' => 'SAR',
            'visitor_id' => 'vid_valid_customer_123',
        ]);

        $this->assertEquals($this->application->id, $conversion->campaign_application_id);
        $this->assertEquals($click->id, $conversion->attribution_click_id);
        $this->assertEquals(ConversionStatus::PENDING, $conversion->status);
        $this->assertEquals('50.00', MoneyMath::format($conversion->commission_value, 2)); // 10% of 500
        $this->assertFalse($conversion->is_suspicious);
    }

    /**
     * 2. Test expiration of attribution window
     */
    public function test_attribution_window_expiration_does_not_attribute(): void
    {
        // Click was 35 days ago; window is 30 days
        AttributionClick::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'terms_version_id' => $this->application->terms_version_id,
            'tracking_code' => $this->application->tracking_code,
            'visitor_id' => 'vid_expired_customer_789',
            'session_id' => 'sid_expired',
            'ip_hash' => hash('sha256', '10.0.0.1_salt'),
            'user_agent_hash' => hash('sha256', 'Safari'),
            'country' => 'SA',
            'device_type' => 'mobile',
            'created_at' => now()->subDays(35),
        ]);

        $conversion = $this->attributionService->recordConversion([
            'campaign' => $this->campaign,
            'external_order_id' => 'ORD-EXPIRED-WINDOW-002',
            'order_value' => 300.00,
            'currency' => 'SAR',
            'visitor_id' => 'vid_expired_customer_789',
        ]);

        // Since click is past window and no tracking code was passed at checkout, it is unattributed
        $this->assertNull($conversion->campaign_application_id);
        $this->assertNull($conversion->attribution_click_id);
        $this->assertEquals('0.00', MoneyMath::format($conversion->commission_value, 2));
    }

    /**
     * 3. Test conversion binds to accurate terms version snapshot
     */
    public function test_conversion_records_immutable_terms_snapshot(): void
    {
        $click = AttributionClick::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'terms_version_id' => $this->application->terms_version_id,
            'tracking_code' => $this->application->tracking_code,
            'visitor_id' => 'vid_snap_111',
            'session_id' => 'sid_snap',
            'ip_hash' => hash('sha256', '1.1.1.1'),
            'user_agent_hash' => hash('sha256', 'Chrome'),
            'created_at' => now()->subMinutes(5),
        ]);

        $conversion = $this->attributionService->recordConversion([
            'campaign' => $this->campaign,
            'external_order_id' => 'ORD-TERMS-SNAP-003',
            'order_value' => 1000.00,
            'currency' => 'SAR',
            'visitor_id' => 'vid_snap_111',
        ]);

        $this->assertNotNull($conversion->commission_terms_snapshot);
        $snapshot = $conversion->commission_terms_snapshot;
        $this->assertEquals(1, $snapshot['version_number']);
        $this->assertEquals('PERCENTAGE_CPO', $snapshot['commission_type']);
        $this->assertEquals('100', $snapshot['calculated_commission']);
        $this->assertEquals('1000', $snapshot['order_value']);
        $this->assertEquals('100.00', $conversion->commission_value);
    }

    /**
     * 4. Test client cannot spoof or overwrite marketer ID or commission
     */
    public function test_server_authoritative_calculation_ignores_client_tampered_commission_or_marketer(): void
    {
        $tamperedPayload = [
            'campaign' => $this->campaign,
            'external_order_id' => 'ORD-TAMPER-004',
            'order_value' => 200.00,
            'currency' => 'SAR',
            'tracking_code' => $this->application->tracking_code,
            // Malicious fields sent by client trying to set 900 commission or change status to APPROVED
            'commission_value' => 900.00,
            'status' => 'APPROVED',
            'campaign_application_id' => 'fake-application-uuid',
        ];

        $conversion = $this->attributionService->recordConversion($tamperedPayload);

        // Server strictly calculates 10% of 200 = 20.00, ignoring 900.00
        $this->assertEquals('20.00', MoneyMath::format($conversion->commission_value, 2));
        $this->assertEquals(ConversionStatus::PENDING, $conversion->status);
        $this->assertEquals($this->application->id, $conversion->campaign_application_id);
    }

    /**
     * 5. Test precision MoneyMath across all commission types (CPO, Fixed, CPA, UGC, Hybrid)
     */
    public function test_money_math_precision_across_all_commission_types(): void
    {
        // 5.1 CPO Percentage
        $cpoCampaign = new Campaign([
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15.50',
        ]);
        $cpoCommission = $this->attributionService->calculateCommission($cpoCampaign, '250.75');
        // 250.75 * 0.155 = 38.86625 => scale 2 = 38.86
        $this->assertEquals(38.86, $cpoCommission);

        // 5.2 Fixed Per Order
        $fixedCampaign = new Campaign([
            'commission_type' => CampaignCommissionType::FIXED_PER_ORDER,
            'commission_value' => '75.00',
        ]);
        $fixedCommission = $this->attributionService->calculateCommission($fixedCampaign, '500.00');
        $this->assertEquals(75.00, $fixedCommission);

        // 5.3 CPA Action
        $cpaCampaign = new Campaign([
            'commission_type' => CampaignCommissionType::CPA_ACTION,
            'commission_value' => '40.00',
        ]);
        $cpaCommission = $this->attributionService->calculateCommission($cpaCampaign, '10.00');
        $this->assertEquals(40.00, $cpaCommission);

        // 5.4 UGC Flat Fee
        $ugcCampaign = new Campaign([
            'commission_type' => CampaignCommissionType::UGC_FLAT_FEE,
            'commission_value' => '500.00',
        ]);
        $ugcCommission = $this->attributionService->calculateCommission($ugcCampaign, '0.00');
        $this->assertEquals(500.00, $ugcCommission);

        // 5.5 Hybrid Base
        $hybridCampaign = new Campaign([
            'commission_type' => CampaignCommissionType::HYBRID,
            'commission_value' => '120.00',
        ]);
        $hybridCommission = $this->attributionService->calculateCommission($hybridCampaign, '400.00');
        $this->assertEquals(120.00, $hybridCommission);
    }

    /**
     * 6. Test conversion deduplication / idempotency
     */
    public function test_conversion_deduplication_is_strictly_idempotent(): void
    {
        $payload = [
            'campaign' => $this->campaign,
            'external_order_id' => 'ORD-IDEMPOTENT-006',
            'order_value' => 450.00,
            'currency' => 'SAR',
            'tracking_code' => $this->application->tracking_code,
        ];

        $first = $this->attributionService->recordConversion($payload);
        $second = $this->attributionService->recordConversion($payload);

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, Conversion::where('campaign_id', $this->campaign->id)->where('external_order_id', 'ORD-IDEMPOTENT-006')->count());
    }

    /**
     * 7. Test fraud detection: Rapid conversion anomaly (Bot checkout under 3 seconds)
     */
    public function test_rapid_conversion_under_threshold_triggers_fraud_signal(): void
    {
        // Click occurred just 1 second ago!
        $click = AttributionClick::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'terms_version_id' => $this->application->terms_version_id,
            'tracking_code' => $this->application->tracking_code,
            'visitor_id' => 'vid_bot_rapid_007',
            'session_id' => 'sid_bot',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent_hash' => hash('sha256', 'Python-requests'),
            'country' => 'SA',
            'device_type' => 'desktop',
            'created_at' => now()->subSecond(),
        ]);

        $conversion = $this->attributionService->recordConversion([
            'campaign' => $this->campaign,
            'external_order_id' => 'ORD-RAPID-BOT-007',
            'order_value' => 200.00,
            'currency' => 'SAR',
            'visitor_id' => 'vid_bot_rapid_007',
            'occurred_at' => now(),
        ]);

        $this->assertTrue($conversion->is_suspicious);
        $this->assertGreaterThanOrEqual(40, $conversion->fraud_risk_score);
        $this->assertNotEmpty($conversion->fraud_signals);

        $ruleKeys = array_column($conversion->fraud_signals, 'rule_key');
        $this->assertContains('rapid_conversion_seconds', $ruleKeys);

        // Crucial requirement: Suspicious conversion remains PENDING and is not auto-rejected blindly!
        $this->assertEquals(ConversionStatus::PENDING, $conversion->status);

        // Audit log recorded
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'Conversion',
            'entity_id' => $conversion->id,
            'action' => AuditAction::CONVERSION_FLAGGED_SUSPICIOUS->value,
        ]);
    }

    /**
     * 8. Test fraud detection: Click burst density anomaly
     */
    public function test_click_burst_triggers_fraud_signal(): void
    {
        $ipHash = hash('sha256', '203.0.113.5_burst');
        $visitorId = 'vid_burst_user_008';

        // Simulate 20 clicks in last 30 seconds (threshold is 15 in 60s)
        for ($i = 0; $i < 20; $i++) {
            AttributionClick::create([
                'campaign_id' => $this->campaign->id,
                'campaign_application_id' => $this->application->id,
                'terms_version_id' => $this->application->terms_version_id,
                'tracking_code' => $this->application->tracking_code,
                'visitor_id' => $visitorId,
                'session_id' => 'sid_' . $i,
                'ip_hash' => $ipHash,
                'user_agent_hash' => hash('sha256', 'ScraperBot'),
                'country' => 'SA',
                'device_type' => 'desktop',
                'created_at' => now()->subSeconds(20),
            ]);
        }

        $latestClick = AttributionClick::where('visitor_id', $visitorId)->latest('created_at')->first();

        $conversion = $this->attributionService->recordConversion([
            'campaign' => $this->campaign,
            'external_order_id' => 'ORD-BURST-008',
            'order_value' => 350.00,
            'currency' => 'SAR',
            'visitor_id' => $visitorId,
            'occurred_at' => now()->addMinutes(1), // occurred 1 min after click
        ]);

        $this->assertGreaterThanOrEqual(35, $conversion->fraud_risk_score);
        $ruleKeys = array_column($conversion->fraud_signals ?? [], 'rule_key');
        $this->assertContains('click_burst_limit', $ruleKeys);
    }

    /**
     * 9. Test admin investigation: Clear suspicion vs confirm fraud & reject
     */
    public function test_admin_investigation_clearing_and_rejecting_workflows(): void
    {
        // Create suspicious conversion
        $conv = Conversion::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'external_order_id' => 'ORD-INVESTIGATE-009',
            'conversion_type' => 'ORDER',
            'status' => ConversionStatus::PENDING,
            'order_value' => '600.00',
            'currency' => 'SAR',
            'commission_value' => '60.00',
            'fraud_risk_score' => 65,
            'is_suspicious' => true,
            'fraud_signals' => [
                ['rule_key' => 'click_burst_limit', 'weight' => 35, 'name_ar' => 'كثافة نقرات'],
                ['rule_key' => 'conversion_velocity_window', 'weight' => 30, 'name_ar' => 'تكرار تحويلات'],
            ],
        ]);

        // 9.1 Super Admin clears suspicion
        $this->actingAs($this->superAdmin);
        $response = $this->post(route('admin.conversions.investigate', $conv->id), [
            'decision' => 'CLEAR_SUSPICION',
            'notes' => 'تم فحص حركة المتجر مع العميل وتبين أنه مشتري حقيقي أجرى طلبين متتاليين بعناوين شحن مختلفة.',
        ]);

        $response->assertRedirect();
        $conv->refresh();

        $this->assertFalse($conv->is_suspicious);
        $this->assertNotNull($conv->investigated_at);
        $this->assertEquals($this->superAdmin->id, $conv->investigated_by_user_id);
        $this->assertStringContainsString('مشتري حقيقي', $conv->investigation_notes);

        // 9.2 Another suspicious conversion confirmed as fraud & rejected
        $fraudConv = Conversion::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'external_order_id' => 'ORD-FRAUD-CONFIRM-010',
            'conversion_type' => 'ORDER',
            'status' => ConversionStatus::PENDING,
            'order_value' => '800.00',
            'currency' => 'SAR',
            'commission_value' => '80.00',
            'fraud_risk_score' => 80,
            'is_suspicious' => true,
        ]);

        $rejectResponse = $this->post(route('admin.conversions.investigate', $fraudConv->id), [
            'decision' => 'CONFIRM_FRAUD_REJECT',
            'notes' => 'تم رصد سلوك بطاقات ائتمانية مسروقة ومطابقة البصمة مع قائمة سوداء.',
        ]);

        $rejectResponse->assertRedirect();
        $fraudConv->refresh();

        $this->assertEquals(ConversionStatus::REJECTED, $fraudConv->status);
        $this->assertStringContainsString('تم الرفض الأمني بعد التحقيق الإداري', $fraudConv->rejection_reason);
    }

    /**
     * 10. Test double-entry commission settlement and prevention of double-crediting
     */
    public function test_prevent_double_settlement_and_post_exact_ledger_entries(): void
    {
        // Fund Brand Campaign Escrow wallet first
        $brandWallet = $this->walletService->getOrCreateWallet($this->brandUser, Currency::SAR);
        $brandWallet->update([
            'reserved_balance' => '1000.0000', // Reserved escrow for campaign
        ]);

        $marketerWallet = $this->walletService->getOrCreateWallet($this->marketerUser, Currency::SAR);
        $initialMarketerAvailable = $marketerWallet->available_balance;

        $conversion = Conversion::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'external_order_id' => 'ORD-SETTLE-011',
            'status' => ConversionStatus::APPROVED,
            'order_value' => '1000.00',
            'currency' => 'SAR',
            'commission_value' => '100.00',
            'commission_terms_snapshot' => [
                'calculated_commission' => '100.00',
            ],
        ]);

        // First settlement
        $settlement1 = $this->settlementService->settleConversion($conversion, $this->brandUser);
        $this->assertNotNull($settlement1);

        $marketerWallet->refresh();
        $brandWallet->refresh();

        $this->assertEquals('100.0000', MoneyMath::format($marketerWallet->available_balance, 4));
        $this->assertEquals('900.0000', MoneyMath::format($brandWallet->reserved_balance, 4));

        // Second settlement attempt on same conversion must be idempotent
        $settlement2 = $this->settlementService->settleConversion($conversion, $this->brandUser);
        $this->assertEquals($settlement1->id, $settlement2->id);

        $marketerWallet->refresh();
        $this->assertEquals('100.0000', MoneyMath::format($marketerWallet->available_balance, 4)); // Did NOT double-credit!
    }

    /**
     * 11. Test cancellation, reversal and marketer balance protection
     */
    public function test_cancellation_reverses_ledger_and_protects_marketer_wallet(): void
    {
        // Setup funded escrow and settled conversion
        $brandWallet = $this->walletService->getOrCreateWallet($this->brandUser, Currency::SAR);
        $brandWallet->update(['reserved_balance' => '500.0000']);

        $conversion = Conversion::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'external_order_id' => 'ORD-REVERSAL-012',
            'status' => ConversionStatus::APPROVED,
            'order_value' => '500.00',
            'currency' => 'SAR',
            'commission_value' => '50.00',
        ]);

        $this->settlementService->settleConversion($conversion, $this->brandUser);

        // Cancel via Brand controller
        $this->actingAs($this->brandUser);
        $response = $this->post(route('brand.conversions.cancel', $conversion->id), [
            'reason' => 'تم استرجاع السلعة من المتجر',
        ]);

        $response->assertRedirect();
        $conversion->refresh();

        $this->assertEquals(ConversionStatus::CANCELLED, $conversion->status);
        $settlement = $conversion->settlement;
        $this->assertTrue($settlement->isReversed());
        $this->assertNotNull($settlement->reversal_ledger_transaction_id);

        // Verify marketer wallet available balance clawed back cleanly
        $marketerWallet = $this->walletService->getOrCreateWallet($this->marketerUser, Currency::SAR);
        $this->assertEquals('0.0000', MoneyMath::format($marketerWallet->available_balance, 4));
    }

    /**
     * 12. Test brand tenancy isolation: Brand A cannot view or manage Brand B conversions
     */
    public function test_brand_tenancy_isolation_strictly_enforced(): void
    {
        $conversion = Conversion::create([
            'campaign_id' => $this->campaign->id, // Belongs to Brand A
            'campaign_application_id' => $this->application->id,
            'external_order_id' => 'ORD-TENANCY-013',
            'status' => ConversionStatus::PENDING,
            'order_value' => '200.00',
            'currency' => 'SAR',
            'commission_value' => '20.00',
        ]);

        // Attempt approve by Brand B user -> Forbidden 403
        $this->actingAs($this->otherBrandUser);
        $response = $this->post(route('brand.conversions.approve', $conversion->id));
        $response->assertStatus(403);

        // Attempt cancel by Brand B user -> Forbidden 403
        $response = $this->post(route('brand.conversions.cancel', $conversion->id));
        $response->assertStatus(403);
    }

    /**
     * 13. Test state transitions: Cannot approve rejected or cancelled conversion without authorization
     */
    public function test_state_transition_guards_prevent_illegal_reopening(): void
    {
        $conversion = Conversion::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'external_order_id' => 'ORD-GUARD-014',
            'status' => ConversionStatus::REJECTED,
            'rejection_reason' => 'مرفوض مسبقاً',
            'order_value' => '300.00',
            'currency' => 'SAR',
            'commission_value' => '30.00',
        ]);

        // Brand user attempts to approve an already REJECTED conversion
        $this->actingAs($this->brandUser);
        $response = $this->post(route('brand.conversions.approve', $conversion->id));
        $response->assertSessionHas('error');

        $conversion->refresh();
        $this->assertEquals(ConversionStatus::REJECTED, $conversion->status); // Remains rejected!
    }

    /**
     * 14. Test updating fraud detection rules requires RBAC permission
     */
    public function test_fraud_detection_rules_management_rbac(): void
    {
        $rule = FraudDetectionRule::first();

        // Marketer or unauthorized user cannot update rules
        $this->actingAs($this->marketerUser);
        $response = $this->put(route('admin.conversions.rules.update', $rule->id), [
            'threshold_value' => '5',
            'risk_weight' => 50,
            'is_active' => 1,
        ]);
        $response->assertStatus(403);

        // Super Admin can update
        $this->actingAs($this->superAdmin);
        $response = $this->put(route('admin.conversions.rules.update', $rule->id), [
            'threshold_value' => '5',
            'risk_weight' => 50,
            'is_active' => 1,
        ]);
        $response->assertRedirect();

        $rule->refresh();
        $this->assertEquals('5', $rule->threshold_value);
        $this->assertEquals(50, $rule->risk_weight);
    }
}
