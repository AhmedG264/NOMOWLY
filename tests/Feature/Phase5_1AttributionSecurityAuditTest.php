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
use App\Models\BrandIntegration;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\Conversion;
use App\Models\MarketerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5_1AttributionSecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $brandAUser;
    protected BrandProfile $brandAProfile;
    protected BrandIntegration $brandAIntegration;
    protected Campaign $campaignA;

    protected User $brandBUser;
    protected BrandProfile $brandBProfile;
    protected BrandIntegration $brandBIntegration;
    protected Campaign $campaignB;

    protected User $marketerUser;
    protected MarketerProfile $marketerProfile;
    protected CampaignApplication $approvedApplication;
    protected CampaignApplication $pendingApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Brand A
        $this->brandAUser = User::create([
            'name' => 'Brand Alpha Store',
            'email' => 'alpha@brand.com',
            'password' => bcrypt('StrongPass123!'),
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->brandAProfile = BrandProfile::create([
            'user_id' => $this->brandAUser->id,
            'company_name' => 'Brand Alpha',
            'currency' => 'SAR',
        ]);

        $this->brandAIntegration = BrandIntegration::create([
            'brand_profile_id' => $this->brandAProfile->id,
            'name' => 'Alpha Salla Store Webhook',
            'provider' => 'SALLA',
            'api_key' => 'nmw_live_alpha_key_1111222233334444',
            'api_secret' => 'sec_live_alpha_secret_5555666677778888',
            'status' => 'ACTIVE',
        ]);

        $this->campaignA = Campaign::create([
            'brand_profile_id' => $this->brandAProfile->id,
            'title' => 'حملة المتجر ألفا للأزياء',
            'slug' => 'alpha-fashion-campaign',
            'destination_url' => 'https://alpha-store.com/catalog',
            'category' => 'أزياء',
            'category_slug' => 'fashion',
            'description' => 'حملة متجر ألفا التجريبي.',
            'brief' => 'شروط حملة ألفا وتفاصيلها.',
            'requirements' => 'ريلز ومراجعات حقيقية.',
            'target_markets' => ['السعودية', 'الإمارات'],
            'currencies' => ['SAR', 'AED'],
            'tracking_methods' => ['رابط تتبع ذكي', 'كود خصم حصري'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15', // 15%
            'cookie_duration_days' => 30,
            'status' => CampaignStatus::ACTIVE,
        ]);

        // 2. Create Brand B (for cross-brand attack tests)
        $this->brandBUser = User::create([
            'name' => 'Brand Beta Store',
            'email' => 'beta@brand.com',
            'password' => bcrypt('StrongPass123!'),
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->brandBProfile = BrandProfile::create([
            'user_id' => $this->brandBUser->id,
            'company_name' => 'Brand Beta',
            'currency' => 'SAR',
        ]);

        $this->brandBIntegration = BrandIntegration::create([
            'brand_profile_id' => $this->brandBProfile->id,
            'name' => 'Beta Zid Store Webhook',
            'provider' => 'ZID',
            'api_key' => 'nmw_live_beta_key_9999888877776666',
            'api_secret' => 'sec_live_beta_secret_4444333322221111',
            'status' => 'ACTIVE',
        ]);

        $this->campaignB = Campaign::create([
            'brand_profile_id' => $this->brandBProfile->id,
            'title' => 'حملة متجر بيتا للإلكترونيات',
            'slug' => 'beta-tech-campaign',
            'destination_url' => 'https://beta-store.com/electronics',
            'category' => 'إلكترونيات',
            'category_slug' => 'electronics',
            'description' => 'حملة متجر بيتا.',
            'brief' => 'شروط بيتا.',
            'requirements' => 'فيديو ريفيو.',
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'tracking_methods' => ['كود خصم حصري'],
            'commission_type' => CampaignCommissionType::FIXED_PER_ORDER,
            'commission_value' => '50.00',
            'cookie_duration_days' => 14,
            'status' => CampaignStatus::ACTIVE,
        ]);

        // 3. Marketer User & Applications
        $this->marketerUser = User::create([
            'name' => 'Youssef Creator',
            'email' => 'youssef@creator.com',
            'password' => bcrypt('StrongPass123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->marketerProfile = MarketerProfile::create([
            'user_id' => $this->marketerUser->id,
            'full_name' => 'Youssef Performance Marketer',
            'country' => 'SA',
            'preferred_currency' => 'SAR',
        ]);

        $this->approvedApplication = CampaignApplication::create([
            'campaign_id' => $this->campaignA->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'ALPHA-YOUSSEF-88',
            'tracking_link' => url('/go/alpha-youssef-88?ref=alpha-youssef-88'),
            'applied_at' => now()->subDays(2),
            'reviewed_at' => now()->subDay(),
        ]);

        $pendingMarketerUser = User::create([
            'name' => 'Pending Creator',
            'email' => 'pending@creator.com',
            'password' => bcrypt('StrongPass123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $pendingMarketerProfile = MarketerProfile::create([
            'user_id' => $pendingMarketerUser->id,
            'full_name' => 'Pending Marketer',
            'country' => 'SA',
            'preferred_currency' => 'SAR',
        ]);

        $this->pendingApplication = CampaignApplication::create([
            'campaign_id' => $this->campaignA->id,
            'marketer_profile_id' => $pendingMarketerProfile->id,
            'status' => CampaignApplicationStatus::PENDING,
            'tracking_code' => 'ALPHA-PENDING-00',
            'applied_at' => now(),
        ]);
    }

    /**
     * Helper to sign a conversion request canonical payload
     */
    protected function makeSignedRequest(array $payload, ?BrandIntegration $integration = null, ?int $timestamp = null, ?string $customSignature = null): \Illuminate\Testing\TestResponse
    {
        $integration = $integration ?? $this->brandAIntegration;
        $timestamp = $timestamp ?? time();
        $rawBody = json_encode($payload);

        $signature = $customSignature ?? hash_hmac('sha256', "{$timestamp}.{$rawBody}", $integration->api_secret);

        return $this->withHeaders([
            'X-Nomowly-Key' => $integration->api_key,
            'X-Nomowly-Timestamp' => (string) $timestamp,
            'X-Nomowly-Signature' => $signature,
        ])->postJson('/api/conversions/ingest', $payload);
    }

    /**
     * 1. Unauthenticated conversion ingestion rejected
     */
    public function test_unauthenticated_conversion_ingestion_rejected(): void
    {
        $response = $this->postJson('/api/conversions/ingest', [
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-UNAUTH-01',
            'order_value' => 500.00,
        ]);

        $response->assertStatus(401);
        $response->assertJson(['success' => false, 'error' => 'unauthenticated']);
    }

    /**
     * 2. Invalid API credentials rejected
     */
    public function test_invalid_api_credentials_rejected(): void
    {
        $payload = ['campaign_id' => $this->campaignA->id, 'external_order_id' => 'ORD-001', 'order_value' => 100.00];
        $timestamp = time();

        $response = $this->withHeaders([
            'X-Nomowly-Key' => 'nmw_invalid_fake_key_99999999',
            'X-Nomowly-Timestamp' => (string) $timestamp,
            'X-Nomowly-Signature' => 'fake_signature',
        ])->postJson('/api/conversions/ingest', $payload);

        $response->assertStatus(401);
        $response->assertJson(['success' => false, 'error' => 'invalid_credentials']);
    }

    /**
     * 3. Invalid API signature rejected
     */
    public function test_invalid_api_signature_rejected(): void
    {
        $payload = [
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-BADSIG-01',
            'order_value' => 350.00,
        ];

        $response = $this->makeSignedRequest($payload, $this->brandAIntegration, time(), 'corrupted_or_forged_hmac_signature');

        $response->assertStatus(401);
        $response->assertJson(['success' => false, 'error' => 'invalid_signature']);
    }

    /**
     * 4. Expired signature (> 5 minutes past) rejected
     */
    public function test_expired_signature_rejected(): void
    {
        $payload = [
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-EXPIRED-SIG',
            'order_value' => 200.00,
        ];

        // 10 minutes in the past (> 300s tolerance)
        $expiredTimestamp = time() - 600;

        $response = $this->makeSignedRequest($payload, $this->brandAIntegration, $expiredTimestamp);

        $response->assertStatus(401);
        $response->assertJson(['success' => false, 'error' => 'timestamp_out_of_bounds']);
    }

    /**
     * 5. Future timestamp (> 5 minutes future) rejected
     */
    public function test_future_timestamp_rejected(): void
    {
        $payload = [
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-FUTURE-SIG',
            'order_value' => 200.00,
        ];

        // 10 minutes in the future (> 300s tolerance)
        $futureTimestamp = time() + 600;

        $response = $this->makeSignedRequest($payload, $this->brandAIntegration, $futureTimestamp);

        $response->assertStatus(401);
        $response->assertJson(['success' => false, 'error' => 'timestamp_out_of_bounds']);
    }

    /**
     * 6. Replayed event is strictly idempotent with no duplicate conversion or audits
     */
    public function test_replayed_event_is_strictly_idempotent(): void
    {
        $payload = [
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-REPLAY-99',
            'order_value' => 450.00,
            'tracking_code' => 'ALPHA-YOUSSEF-88',
        ];

        $timestamp = time();

        // Send first request
        $res1 = $this->makeSignedRequest($payload, $this->brandAIntegration, $timestamp);
        $res1->assertStatus(200);

        // Replay exact same request
        $res2 = $this->makeSignedRequest($payload, $this->brandAIntegration, $timestamp);
        $res2->assertStatus(200);

        // Only one conversion record exists
        $this->assertEquals(1, Conversion::where('campaign_id', $this->campaignA->id)->where('external_order_id', 'ORD-REPLAY-99')->count());

        // Only one CONVERSION_RECEIVED audit log exists for this order
        $auditCount = AuditLog::where('action', AuditAction::CONVERSION_RECEIVED)
            ->where('payload_diff', 'like', '%ORD-REPLAY-99%')
            ->count();
        $this->assertEquals(1, $auditCount);
    }

    /**
     * 7. Duplicate external order rejected or idempotent across payloads
     */
    public function test_duplicate_external_order_deduplicated(): void
    {
        $payload1 = [
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-DEDUP-01',
            'order_value' => 300.00,
        ];
        $res1 = $this->makeSignedRequest($payload1);
        $res1->assertStatus(200);

        // Submit same external order with different value attempt
        $payload2 = [
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-DEDUP-01',
            'order_value' => 999.00,
        ];
        $res2 = $this->makeSignedRequest($payload2);
        $res2->assertStatus(200);

        // Stored value remains original 300.00
        $conv = Conversion::where('campaign_id', $this->campaignA->id)->where('external_order_id', 'ORD-DEDUP-01')->first();
        $this->assertEquals(300.00, (float) $conv->order_value);
    }

    /**
     * 8. Cannot spoof marketer ID or attribution status
     */
    public function test_cannot_spoof_marketer_id(): void
    {
        $payload = [
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-SPOOF-01',
            'order_value' => 500.00,
            'marketer_id' => '00000000-fake-marketer-id-00000000',
            'is_attributed' => true,
            'status' => 'APPROVED',
            'commission_value' => 999.99,
        ];

        $res = $this->makeSignedRequest($payload);
        $res->assertStatus(200);

        $conv = Conversion::where('external_order_id', 'ORD-SPOOF-01')->first();
        $this->assertNotNull($conv);
        // Server forces status PENDING (not client-supplied APPROVED)
        $this->assertEquals(ConversionStatus::PENDING, $conv->status);
        // Server calculates commission (not client-supplied 999.99)
        $this->assertEquals(0.00, (float) $conv->commission_value); // Unattributed because no valid click or tracking code
    }

    /**
     * 9. Cannot spoof campaign ownership (Brand A integration cannot ingest for Campaign B)
     */
    public function test_cannot_spoof_campaign_ownership(): void
    {
        // Brand A uses its valid key to record conversion for Brand B's campaign
        $payload = [
            'campaign_id' => $this->campaignB->id, // Belongs to Brand B
            'external_order_id' => 'ORD-CROSS-CAMP-01',
            'order_value' => 200.00,
        ];

        $res = $this->makeSignedRequest($payload, $this->brandAIntegration);
        $res->assertStatus(403);
        $res->assertJson(['success' => false, 'error' => 'unauthorized_campaign']);
    }

    /**
     * 10. Cannot submit another campaign's tracking code
     */
    public function test_cannot_submit_another_campaigns_tracking_code(): void
    {
        // Create an approved application on Campaign B with code 'BETA-CODE-77'
        CampaignApplication::create([
            'campaign_id' => $this->campaignB->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'BETA-CODE-77',
        ]);

        // Attempt to use BETA-CODE-77 on Campaign A
        $payload = [
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-CODE-SPOOF-01',
            'order_value' => 300.00,
            'tracking_code' => 'BETA-CODE-77',
        ];

        $res = $this->makeSignedRequest($payload, $this->brandAIntegration);
        $res->assertStatus(422);
        $res->assertJson(['success' => false, 'error' => 'invalid_tracking_code']);
    }

    /**
     * 11. Cannot submit unapproved marketer's tracking code
     */
    public function test_cannot_submit_unapproved_marketers_tracking_code(): void
    {
        $payload = [
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-UNAPPROVED-01',
            'order_value' => 300.00,
            'tracking_code' => $this->pendingApplication->tracking_code, // PENDING status
        ];

        $res = $this->makeSignedRequest($payload, $this->brandAIntegration);
        $res->assertStatus(422);
        $res->assertJson(['success' => false, 'error' => 'unapproved_marketer']);
    }

    /**
     * 12. Cannot submit invalid or negative order value
     */
    public function test_cannot_submit_invalid_or_negative_order_value(): void
    {
        // Negative order value
        $resNegative = $this->makeSignedRequest([
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-NEG-01',
            'order_value' => -50.00,
        ]);
        $resNegative->assertStatus(422);

        // Zero order value
        $resZero = $this->makeSignedRequest([
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-ZERO-01',
            'order_value' => 0.00,
        ]);
        $resZero->assertStatus(422);

        // Excessive decimals (> 2 decimal places)
        $resExcessDecimals = $this->makeSignedRequest([
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-DEC-01',
            'order_value' => '100.123',
        ]);
        $resExcessDecimals->assertStatus(422);
    }

    /**
     * 13. Cannot submit unsupported currency
     */
    public function test_cannot_submit_unsupported_currency(): void
    {
        // Campaign A supports SAR and AED only
        $payload = [
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-CURR-01',
            'order_value' => 300.00,
            'currency' => 'EUR', // Unsupported
        ];

        $res = $this->makeSignedRequest($payload);
        $res->assertStatus(422);
        $res->assertJson(['success' => false, 'error' => 'unsupported_currency']);
    }

    /**
     * 14. Cannot create conversion for inactive or paused campaign
     */
    public function test_cannot_create_conversion_for_inactive_campaign(): void
    {
        $this->campaignA->update(['status' => CampaignStatus::PAUSED]);

        $payload = [
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-PAUSED-01',
            'order_value' => 200.00,
        ];

        $res = $this->makeSignedRequest($payload);
        $res->assertStatus(422);
        $res->assertJson(['success' => false, 'error' => 'campaign_not_active']);
    }

    /**
     * 15. Cannot bypass attribution window
     */
    public function test_cannot_bypass_attribution_window(): void
    {
        $visitorId = 'vid_window_test_' . bin2hex(random_bytes(8));

        // Click recorded 40 days ago (window is 30 days)
        AttributionClick::create([
            'campaign_id' => $this->campaignA->id,
            'campaign_application_id' => $this->approvedApplication->id,
            'tracking_code' => $this->approvedApplication->tracking_code,
            'visitor_id' => $visitorId,
            'session_id' => 'sid_window_1',
            'created_at' => now()->subDays(40),
        ]);

        $payload = [
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-WINDOW-01',
            'order_value' => 400.00,
            'visitor_id' => $visitorId,
        ];

        $res = $this->makeSignedRequest($payload);
        $res->assertStatus(200);

        $conv = Conversion::where('external_order_id', 'ORD-WINDOW-01')->first();
        // Attribution expired -> no marketer credited, commission is 0.00
        $this->assertNull($conv->campaign_application_id);
        $this->assertEquals(0.00, (float) $conv->commission_value);
    }

    /**
     * 16. Brand A cannot access or view Brand B conversions
     */
    public function test_brand_a_cannot_view_brand_b_conversions(): void
    {
        // Conversion for Brand B
        $convB = Conversion::create([
            'campaign_id' => $this->campaignB->id,
            'external_order_id' => 'ORD-BETA-EXCLUSIVE',
            'order_value' => 500.00,
            'currency' => 'SAR',
            'commission_value' => 50.00,
            'status' => ConversionStatus::PENDING,
        ]);

        // Brand A views its conversions
        $response = $this->actingAs($this->brandAUser)->get('/brand/conversions');
        $response->assertStatus(200);
        $response->assertDontSee('ORD-BETA-EXCLUSIVE');
    }

    /**
     * 17. Brand A cannot approve or reject Brand B conversion
     */
    public function test_brand_a_cannot_approve_brand_b_conversion(): void
    {
        $convB = Conversion::create([
            'campaign_id' => $this->campaignB->id,
            'external_order_id' => 'ORD-BETA-ATTACK',
            'order_value' => 500.00,
            'currency' => 'SAR',
            'commission_value' => 50.00,
            'status' => ConversionStatus::PENDING,
        ]);

        // Brand A attempts to approve Brand B conversion
        $this->actingAs($this->brandAUser)
            ->post("/brand/conversions/{$convB->id}/approve")
            ->assertStatus(403);

        // Brand A attempts to reject Brand B conversion
        $this->actingAs($this->brandAUser)
            ->post("/brand/conversions/{$convB->id}/reject", ['reason' => 'Unauthorized'])
            ->assertStatus(403);
    }

    /**
     * 18. Marketer cannot approve own conversion
     */
    public function test_marketer_cannot_approve_own_conversion(): void
    {
        $conv = Conversion::create([
            'campaign_id' => $this->campaignA->id,
            'campaign_application_id' => $this->approvedApplication->id,
            'external_order_id' => 'ORD-MARKETER-SELF-APPROVE',
            'order_value' => 300.00,
            'currency' => 'SAR',
            'commission_value' => 45.00,
            'status' => ConversionStatus::PENDING,
        ]);

        // Marketer tries to approve
        $this->actingAs($this->marketerUser)
            ->post("/brand/conversions/{$conv->id}/approve")
            ->assertStatus(403);
    }

    /**
     * 19. Commission is calculated strictly server-side
     */
    public function test_commission_is_calculated_strictly_server_side(): void
    {
        $payload = [
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-SERVER-CALC-01',
            'order_value' => 1000.00,
            'tracking_code' => 'ALPHA-YOUSSEF-88',
            'commission_value' => 999.00, // Attacker tries to claim 999 SAR
        ];

        $res = $this->makeSignedRequest($payload);
        $res->assertStatus(200);

        $conv = Conversion::where('external_order_id', 'ORD-SERVER-CALC-01')->first();
        // Campaign rate is 15% -> strictly 150.00, ignoring client-provided 999.00
        $this->assertEquals(150.00, (float) $conv->commission_value);
    }

    /**
     * 20. Client cannot overwrite calculated commission on approve
     */
    public function test_client_cannot_overwrite_commission_on_approve(): void
    {
        $conv = Conversion::create([
            'campaign_id' => $this->campaignA->id,
            'campaign_application_id' => $this->approvedApplication->id,
            'external_order_id' => 'ORD-IMMUTABLE-01',
            'order_value' => 200.00,
            'currency' => 'SAR',
            'commission_value' => 30.00,
            'status' => ConversionStatus::PENDING,
        ]);

        $this->actingAs($this->brandAUser)
            ->post("/brand/conversions/{$conv->id}/approve", [
                'commission_value' => 500.00, // Attacker attempts to inject commission override
            ])
            ->assertRedirect();

        $conv->refresh();
        $this->assertEquals(30.00, (float) $conv->commission_value);
    }

    /**
     * 21. Tracking endpoint cannot perform open redirect
     */
    public function test_tracking_endpoint_cannot_perform_open_redirect(): void
    {
        // Malicious visitor appends arbitrary ?url= or ?redirect= parameter
        $response = $this->get('/go/ALPHA-YOUSSEF-88?url=https://attacker-malicious-domain.com&redirect=https://evil.org');

        // Must redirect strictly to campaign destination URL, NEVER to attacker domain
        $response->assertRedirect('https://alpha-store.com/catalog');
    }

    /**
     * 22. Secrets are never returned in API responses or audit logs
     */
    public function test_secrets_are_never_returned_in_api_responses_or_audit_logs(): void
    {
        // 1. Integration model serialization hides api_secret
        $integrationArray = $this->brandAIntegration->toArray();
        $this->assertArrayNotHasKey('api_secret', $integrationArray);

        $integrationJson = json_encode($this->brandAIntegration);
        $this->assertStringNotContainsString('sec_live_alpha_secret', $integrationJson);

        // 2. Audit logs never log api_secret or signatures
        $this->makeSignedRequest([
            'campaign_id' => $this->campaignA->id,
            'external_order_id' => 'ORD-SECRET-CHECK-01',
            'order_value' => 150.00,
        ]);

        $auditMatch = AuditLog::where('payload_diff', 'like', '%sec_live_alpha_secret%')
            ->orWhere('payload_diff', 'like', '%nmw_live_alpha_key%')
            ->count();
        $this->assertEquals(0, $auditMatch);
    }
}
