<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\CampaignApplicationStatus;
use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Enums\ConversionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AttributionClick;
use App\Models\AuditLog;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\Conversion;
use App\Models\MarketerProfile;
use App\Models\User;
use App\Services\AttributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5AAttributionEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $brandUser;
    protected BrandProfile $brandProfile;
    protected User $marketerUser;
    protected MarketerProfile $marketerProfile;
    protected Campaign $campaign;
    protected CampaignApplication $application;
    protected \App\Models\BrandIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Brand User & Profile
        $this->brandUser = User::create([
            'name' => 'Sara Couture Store',
            'email' => 'brand@saracouture.com',
            'password' => bcrypt('StrongPassword123!'),
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->brandProfile = BrandProfile::create([
            'user_id' => $this->brandUser->id,
            'company_name' => 'Sara Couture',
            'legal_name' => 'Sara Couture LLC',
            'commercial_reg_no' => '1010111222',
            'website_url' => 'https://saracouture.com',
            'currency' => 'SAR',
        ]);

        // Create Active Brand Integration for tests
        $this->integration = \App\Models\BrandIntegration::create([
            'brand_profile_id' => $this->brandProfile->id,
            'name' => 'Sara Couture Webhook',
            'provider' => 'CUSTOM',
            'api_key' => 'nmw_test_key_1234567890abcdef',
            'api_secret' => 'sec_test_secret_0987654321fedcba',
            'status' => 'ACTIVE',
        ]);

        // 2. Create Marketer User & Profile
        $this->marketerUser = User::create([
            'name' => 'Ahmed Marketer',
            'email' => 'ahmed@creator.com',
            'password' => bcrypt('StrongPassword123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->marketerProfile = MarketerProfile::create([
            'user_id' => $this->marketerUser->id,
            'full_name' => 'Ahmed Kamal',
            'country' => 'SA',
            'preferred_currency' => 'SAR',
        ]);

        // 3. Create Active Campaign with Destination URL & Percentage Commission
        $this->campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfile->id,
            'title' => 'حملة أزياء الصيف 2026',
            'slug' => 'summer-fashion-2026',
            'destination_url' => 'https://saracouture.com/summer-sale',
            'category' => 'أزياء وموضة',
            'category_slug' => 'fashion',
            'description' => 'حملة تسويق أداء للأزياء الصيفية الفاخرة.',
            'brief' => 'تفاصيل الحملة ونموذج الأداء لمنتجات سارة كوتور.',
            'requirements' => 'نشر ريلز أو بوست مع كود التتبع.',
            'target_markets' => ['السعودية', 'الإمارات'],
            'currencies' => ['SAR'],
            'tracking_methods' => ['رابط تتبع ذكي', 'كود خصم حصري'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15', // 15%
            'cookie_duration_days' => 30,
            'status' => CampaignStatus::ACTIVE,
        ]);

        // 4. Create Approved Campaign Application
        $this->application = CampaignApplication::create([
            'campaign_id' => $this->campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'SARA-AHMED-99',
            'tracking_link' => url('/go/sara-ahmed-99?ref=sara-ahmed-99'),
            'applied_at' => now()->subDay(),
            'reviewed_at' => now(),
        ]);
    }

    protected function postSignedConversion(array $payload, ?\App\Models\BrandIntegration $integration = null): \Illuminate\Testing\TestResponse
    {
        $integration = $integration ?? $this->integration;
        $timestamp = time();
        $rawBody = json_encode($payload);
        $signature = hash_hmac('sha256', "{$timestamp}.{$rawBody}", $integration->api_secret);

        return $this->withHeaders([
            'X-Nomowly-Key' => $integration->api_key,
            'X-Nomowly-Timestamp' => (string) $timestamp,
            'X-Nomowly-Signature' => $signature,
        ])->postJson('/api/conversions/ingest', $payload);
    }

    /**
     * 1. Tracking redirect records AttributionClick, hashes IP/UA, and sets visitor cookie
     */
    public function test_tracking_redirect_records_click_and_sets_visitor_cookie(): void
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)',
            'Referer' => 'https://instagram.com/creator_post',
            'CF-IPCountry' => 'SA',
        ])->get('/go/SARA-AHMED-99');

        // Redirects away to destination url
        $response->assertRedirect('https://saracouture.com/summer-sale');

        // Sets first-party HTTP-only visitor cookie
        $response->assertCookie(AttributionService::VISITOR_COOKIE);

        // Click record created in DB
        $this->assertDatabaseCount('attribution_clicks', 1);

        $click = AttributionClick::first();
        $this->assertNotNull($click);
        $this->assertEquals($this->campaign->id, $click->campaign_id);
        $this->assertEquals($this->application->id, $click->campaign_application_id);
        $this->assertEquals('SARA-AHMED-99', $click->tracking_code);
        $this->assertEquals('mobile', $click->device_type);
        $this->assertEquals('SA', $click->country);
        $this->assertEquals('https://instagram.com/creator_post', $click->referrer);
        $this->assertEquals('https://saracouture.com/summer-sale', $click->landing_url);

        // IP is hashed, never stored in raw plaintext
        $this->assertNotNull($click->ip_hash);
        $this->assertEquals(64, strlen($click->ip_hash));
        $this->assertNotEquals('127.0.0.1', $click->ip_hash);

        // User-agent is hashed
        $this->assertNotNull($click->user_agent_hash);
        $this->assertEquals(64, strlen($click->user_agent_hash));

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::ATTRIBUTION_CLICK_RECORDED->value,
            'entity_type' => 'AttributionClick',
            'entity_id' => $click->id,
        ]);
    }

    /**
     * 2. Tracking redirect fails safely for non-existent codes or inactive campaigns
     */
    public function test_tracking_redirect_fails_safely_for_inactive_or_invalid_codes(): void
    {
        // Invalid tracking code
        $resInvalid = $this->get('/go/NON-EXISTENT-CODE');
        $resInvalid->assertRedirect(route('marketplace'));
        $resInvalid->assertSessionHas('error');
        $this->assertDatabaseCount('attribution_clicks', 0);

        // Paused campaign
        $this->campaign->update(['status' => CampaignStatus::PAUSED]);

        $resPaused = $this->get('/go/SARA-AHMED-99');
        $resPaused->assertRedirect(route('marketplace'));
        $resPaused->assertSessionHas('error');
        $this->assertDatabaseCount('attribution_clicks', 0);
    }

    /**
     * 3. Internal conversion attribution matches click within cookie window
     */
    public function test_conversion_attribution_matches_click_within_cookie_window(): void
    {
        // 1. Visitor clicks marketer link
        $visitorId = 'vid_test_' . bin2hex(random_bytes(10));
        $click = AttributionClick::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'tracking_code' => $this->application->tracking_code,
            'visitor_id' => $visitorId,
            'session_id' => 'sid_test_123',
            'created_at' => now()->subDays(5), // 5 days ago (within 30-day window)
        ]);

        // 2. Conversion event occurs
        $response = $this->postSignedConversion([
            'campaign_id' => $this->campaign->id,
            'external_order_id' => 'ORD-987654',
            'order_value' => 500.00,
            'currency' => 'SAR',
            'visitor_id' => $visitorId,
            'conversion_type' => 'ORDER',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'conversion' => [
                'external_order_id' => 'ORD-987654',
                'order_value' => 500.00,
                'currency' => 'SAR',
                'commission_value' => 75.00, // 15% of 500.00
                'status' => 'PENDING',
                'is_attributed' => true,
            ],
        ]);

        $conversion = Conversion::where('external_order_id', 'ORD-987654')->first();
        $this->assertNotNull($conversion);
        $this->assertEquals($click->id, $conversion->attribution_click_id);
        $this->assertEquals($this->application->id, $conversion->campaign_application_id);
        $this->assertEquals(75.00, (float) $conversion->commission_value);
        $this->assertEquals(ConversionStatus::PENDING, $conversion->status);

        // Audit logs verified
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CONVERSION_RECEIVED->value,
            'entity_type' => 'Conversion',
            'entity_id' => $conversion->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CONVERSION_ATTRIBUTED->value,
            'entity_type' => 'Conversion',
            'entity_id' => $conversion->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::COMMISSION_CALCULATED->value,
            'entity_type' => 'Conversion',
            'entity_id' => $conversion->id,
        ]);
    }

    /**
     * 4. Attribution respects cookie window expiration (clicks outside window are not attributed)
     */
    public function test_attribution_respects_cookie_window_expiration(): void
    {
        $visitorId = 'vid_expired_' . bin2hex(random_bytes(10));

        // Click happened 45 days ago (outside the 30-day cookie window)
        AttributionClick::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'tracking_code' => $this->application->tracking_code,
            'visitor_id' => $visitorId,
            'session_id' => 'sid_expired_123',
            'created_at' => now()->subDays(45),
        ]);

        $response = $this->postSignedConversion([
            'campaign_id' => $this->campaign->id,
            'external_order_id' => 'ORD-EXPIRED-01',
            'order_value' => 400.00,
            'visitor_id' => $visitorId,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'conversion' => [
                'external_order_id' => 'ORD-EXPIRED-01',
                'is_attributed' => false,
                'commission_value' => 0.00,
            ],
        ]);

        $conversion = Conversion::where('external_order_id', 'ORD-EXPIRED-01')->first();
        $this->assertNull($conversion->campaign_application_id);
        $this->assertNull($conversion->attribution_click_id);
        $this->assertEquals(0.00, (float) $conversion->commission_value);
    }

    /**
     * 5. Conversion ingestion is strictly idempotent and deduplicated
     */
    public function test_conversion_ingestion_is_strictly_idempotent(): void
    {
        $payload = [
            'campaign_id' => $this->campaign->id,
            'external_order_id' => 'ORD-IDEMPOTENT-001',
            'order_value' => 250.00,
            'tracking_code' => 'SARA-AHMED-99',
        ];

        // First Ingestion
        $res1 = $this->postSignedConversion($payload);
        $res1->assertStatus(200);

        // Second Ingestion of exact same order for this campaign
        $res2 = $this->postSignedConversion($payload);
        $res2->assertStatus(200);

        // Must still be exactly 1 record in database
        $this->assertEquals(1, Conversion::where('campaign_id', $this->campaign->id)->where('external_order_id', 'ORD-IDEMPOTENT-001')->count());
    }

    /**
     * 6. Commission calculation accurately supports different commission models
     */
    public function test_commission_calculation_supports_percentage_and_fixed(): void
    {
        $attributionService = app(AttributionService::class);

        // Percentage CPO (15% on 600 = 90.00)
        $this->campaign->update([
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15',
        ]);
        $this->assertEquals(90.00, $attributionService->calculateCommission($this->campaign, 600.00));

        // Fixed per order (50 SAR flat on any order value)
        $this->campaign->update([
            'commission_type' => CampaignCommissionType::FIXED_PER_ORDER,
            'commission_value' => '50.00',
        ]);
        $this->assertEquals(50.00, $attributionService->calculateCommission($this->campaign, 600.00));
    }

    /**
     * 7. Brand can review, approve, and reject attributed conversions with audit logging
     */
    public function test_brand_can_approve_and_reject_conversions(): void
    {
        $conversion = Conversion::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'external_order_id' => 'ORD-REVIEW-01',
            'order_value' => 300.00,
            'currency' => 'SAR',
            'commission_value' => 45.00,
            'status' => ConversionStatus::PENDING,
            'occurred_at' => now(),
        ]);

        // Brand approves
        $this->actingAs($this->brandUser)
            ->post("/brand/conversions/{$conversion->id}/approve")
            ->assertRedirect();

        $conversion->refresh();
        $this->assertEquals(ConversionStatus::APPROVED, $conversion->status);
        $this->assertNotNull($conversion->verified_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CONVERSION_APPROVED->value,
            'user_id' => $this->brandUser->id,
            'entity_type' => 'Conversion',
            'entity_id' => $conversion->id,
        ]);

        // Brand rejects with reason
        $this->actingAs($this->brandUser)
            ->post("/brand/conversions/{$conversion->id}/reject", ['reason' => 'الطلب مسترجع من العميل'])
            ->assertRedirect();

        $conversion->refresh();
        $this->assertEquals(ConversionStatus::REJECTED, $conversion->status);
        $this->assertEquals('الطلب مسترجع من العميل', $conversion->rejection_reason);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CONVERSION_REJECTED->value,
            'user_id' => $this->brandUser->id,
            'entity_type' => 'Conversion',
            'entity_id' => $conversion->id,
        ]);
    }

    /**
     * 8. Cross-brand isolation: Brand B cannot approve or reject Brand A's conversions
     */
    public function test_cross_brand_cannot_moderate_other_brands_conversions(): void
    {
        $otherBrandUser = User::create([
            'name' => 'Other Store',
            'email' => 'other@brand.com',
            'password' => bcrypt('StrongPassword123!'),
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        BrandProfile::create([
            'user_id' => $otherBrandUser->id,
            'company_name' => 'Other Brand',
            'currency' => 'SAR',
        ]);

        $conversion = Conversion::create([
            'campaign_id' => $this->campaign->id,
            'external_order_id' => 'ORD-SEC-01',
            'order_value' => 200.00,
            'currency' => 'SAR',
            'status' => ConversionStatus::PENDING,
        ]);

        $this->actingAs($otherBrandUser)
            ->post("/brand/conversions/{$conversion->id}/approve")
            ->assertStatus(403);
    }

    /**
     * 9. Marketer and Brand dashboards reflect real attribution counts and zero states
     */
    public function test_dashboards_reflect_real_attribution_counts_and_zero_states(): void
    {
        // 1. Initial zero state
        $brandRes = $this->actingAs($this->brandUser)->get('/brand/campaigns');
        $brandRes->assertStatus(200);
        $brandRes->assertSee('إجمالي النقرات المسجلة');

        $marketerRes = $this->actingAs($this->marketerUser)->get('/marketer/campaigns');
        $marketerRes->assertStatus(200);
        $marketerRes->assertSee('النقرات المسجلة عبر روابطك');

        // 2. Add real click and approved conversion
        $click = AttributionClick::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'tracking_code' => $this->application->tracking_code,
            'visitor_id' => 'vid_dash_1',
            'session_id' => 'sid_dash_1',
            'created_at' => now(),
        ]);

        Conversion::create([
            'campaign_id' => $this->campaign->id,
            'campaign_application_id' => $this->application->id,
            'attribution_click_id' => $click->id,
            'external_order_id' => 'ORD-DASH-1',
            'order_value' => 1000.00,
            'currency' => 'SAR',
            'commission_value' => 150.00,
            'status' => ConversionStatus::APPROVED,
            'occurred_at' => now(),
        ]);

        // Brand dashboard reflects updated numbers
        $brandUpdated = $this->actingAs($this->brandUser)->get('/brand/campaigns');
        $brandUpdated->assertStatus(200);
        $brandUpdated->assertSee('150.00');

        // Marketer dashboard reflects updated earnings
        $marketerUpdated = $this->actingAs($this->marketerUser)->get('/marketer/campaigns');
        $marketerUpdated->assertStatus(200);
        $marketerUpdated->assertSee('150.00');
    }
}
