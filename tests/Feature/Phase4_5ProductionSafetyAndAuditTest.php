<?php

namespace Tests\Feature;

use App\Enums\CampaignApplicationStatus;
use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Enums\MarketerSpecializationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\MarketerProfile;
use App\Models\MarketerSpecialization;
use App\Models\User;
use Database\Seeders\CampaignSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class Phase4_5ProductionSafetyAndAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $brandAUser;
    protected BrandProfile $brandAProfile;
    protected User $brandBUser;
    protected BrandProfile $brandBProfile;
    protected User $marketerUser;
    protected MarketerProfile $marketerProfile;

    protected function setUp(): void
    {
        parent::setUp();

        // Brand A
        $this->brandAUser = User::factory()->create([
            'email' => 'brand_a@nomowly.com',
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $this->brandAProfile = BrandProfile::create([
            'user_id' => $this->brandAUser->id,
            'company_name' => 'Brand A Store',
            'currency' => 'SAR',
        ]);

        // Brand B
        $this->brandBUser = User::factory()->create([
            'email' => 'brand_b@nomowly.com',
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $this->brandBProfile = BrandProfile::create([
            'user_id' => $this->brandBUser->id,
            'company_name' => 'Brand B Store',
            'currency' => 'EGP',
        ]);

        // Marketer
        $this->marketerUser = User::factory()->create([
            'email' => 'marketer_tester@nomowly.com',
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $this->marketerProfile = MarketerProfile::create([
            'user_id' => $this->marketerUser->id,
            'full_name' => 'مسوق تجريبي',
            'country' => 'السعودية',
        ]);
        MarketerSpecialization::create([
            'marketer_profile_id' => $this->marketerProfile->id,
            'specialization' => MarketerSpecializationType::CREATOR,
            'is_primary' => true,
        ]);
    }

    /**
     * 1. Production does not automatically load demo campaigns.
     */
    public function test_production_environment_blocks_demo_seeding_in_database_seeder(): void
    {
        // Simulate production environment
        App::detectEnvironment(fn() => 'production');
        $this->assertEquals('production', app()->environment());

        $initialCampaignCount = Campaign::count();
        $initialUserCount = User::count();

        // Run DatabaseSeeder
        $seeder = new DatabaseSeeder();
        $seeder->run();

        // Assert no demo campaigns or demo users were added
        $this->assertEquals($initialCampaignCount, Campaign::count());
        $this->assertEquals($initialUserCount, User::count());
        $this->assertDatabaseMissing('users', ['email' => 'brand@nomowly.com']);
    }

    /**
     * 2. Demo seeders directly abort when invoked in production environment.
     */
    public function test_campaign_seeder_directly_aborts_in_production(): void
    {
        App::detectEnvironment(fn() => 'production');

        $initialCount = Campaign::count();

        $campaignSeeder = new CampaignSeeder();
        $campaignSeeder->run();

        $this->assertEquals($initialCount, Campaign::count());
        $this->assertDatabaseMissing('campaigns', ['slug' => 'sara-couture-fashion']);
    }

    /**
     * 3. Hard-coded production credentials are not used or exposed.
     */
    public function test_env_example_has_no_hardcoded_admin_password(): void
    {
        $envExampleContent = file_get_contents(base_path('.env.example'));
        $this->assertStringContainsString('ADMIN_INITIAL_PASSWORD=', $envExampleContent);
        // Ensure no actual password is set in .env.example
        $this->assertMatchesRegularExpression('/^ADMIN_INITIAL_PASSWORD=\s*$/m', $envExampleContent);
    }

    /**
     * 4. Conversion rate is presented as Brand Estimate, not actual measured platform metric.
     */
    public function test_conversion_rate_is_labeled_as_brand_estimate(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandAProfile->id,
            'title' => 'حملة تدقيق معدل التحويل',
            'slug' => 'audit-conv-rate',
            'category' => 'أزياء وموضة',
            'category_slug' => 'fashion',
            'description' => 'وصف',
            'brief' => 'موجز',
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15%',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['كود خصم'],
            'conversion_rate' => '5.5%',
            'status' => CampaignStatus::ACTIVE,
        ]);

        $response = $this->get(route('marketplace.show', $campaign->slug));
        $response->assertStatus(200);
        $response->assertSee('5.5%');
        $response->assertSee('معدل التحويل التقديري');
        $response->assertDontSee('100% موثق عبر المنصة');
    }

    /**
     * 5. Avg basket is presented as Brand Estimate, not actual historical performance.
     */
    public function test_avg_basket_is_labeled_as_brand_estimate(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandAProfile->id,
            'title' => 'حملة تدقيق سلة الشراء',
            'slug' => 'audit-avg-basket',
            'category' => 'أزياء وموضة',
            'category_slug' => 'fashion',
            'description' => 'وصف',
            'brief' => 'موجز',
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15%',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['كود خصم'],
            'avg_basket' => '450 ر.س',
            'status' => CampaignStatus::ACTIVE,
        ]);

        $response = $this->get(route('marketplace.show', $campaign->slug));
        $response->assertStatus(200);
        $response->assertSee('450 ر.س');
        $response->assertSee('سلة الشراء التقديرية');
    }

    /**
     * 6. Tracking code creation does not create earnings or ledger balances.
     */
    public function test_tracking_code_creation_does_not_create_earnings_or_balances(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandAProfile->id,
            'title' => 'حملة أمان مالي',
            'slug' => 'financial-safety-camp',
            'category' => 'أزياء وموضة',
            'category_slug' => 'fashion',
            'description' => 'وصف',
            'brief' => 'موجز',
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15%',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['كود خصم'],
            'status' => CampaignStatus::ACTIVE,
        ]);

        $application = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'status' => CampaignApplicationStatus::PENDING,
            'applied_at' => now(),
        ]);

        // Brand approves application
        $this->actingAs($this->brandAUser)
            ->post(route('brand.campaigns.applications.review', [$campaign, $application]), [
                'action' => 'approve',
                'tracking_code' => 'SAFE-CODE-99',
            ]);

        $application->refresh();
        $this->assertEquals(CampaignApplicationStatus::APPROVED, $application->status);
        $this->assertEquals('SAFE-CODE-99', $application->tracking_code);

        // Verify NO earnings/balance tables are touched
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('payouts') && \Illuminate\Support\Facades\DB::table('payouts')->count() > 0);
    }

    /**
     * 7. Tracking link creation does not create conversions or click records.
     */
    public function test_tracking_link_creation_is_pure_identifier(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandAProfile->id,
            'title' => 'حملة معرف الإسناد',
            'slug' => 'attribution-identifier-camp',
            'category' => 'أزياء وموضة',
            'category_slug' => 'fashion',
            'description' => 'وصف',
            'brief' => 'موجز',
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15%',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['رابط تتبع'],
            'status' => CampaignStatus::ACTIVE,
        ]);

        $application = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'status' => CampaignApplicationStatus::APPROVED,
            'applied_at' => now(),
        ]);

        $application->generateTrackingCredentials();
        $application->save();

        $this->assertNotNull($application->tracking_link);
        $this->assertStringContainsString('ref=', $application->tracking_link);

        // Verify no conversion records were manufactured
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('conversions') && \Illuminate\Support\Facades\DB::table('conversions')->count() > 0);
    }

    /**
     * 8. Paused campaigns cannot receive new applications (Enforced server-side).
     */
    public function test_paused_campaign_cannot_receive_new_applications(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandAProfile->id,
            'title' => 'حملة متوقفة مؤقتاً',
            'slug' => 'paused-camp',
            'category' => 'أزياء وموضة',
            'category_slug' => 'fashion',
            'description' => 'وصف',
            'brief' => 'موجز',
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15%',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['كود خصم'],
            'status' => CampaignStatus::PAUSED,
        ]);

        $response = $this->actingAs($this->marketerUser)
            ->post(route('marketer.campaigns.apply', $campaign), [
                'pitch_note' => 'محاولة تقديم على حملة متوقفة',
            ]);

        // Must be rejected with 403 Forbidden
        $response->assertStatus(403);
        $this->assertDatabaseMissing('campaign_applications', [
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
        ]);
    }

    /**
     * 9. Completed campaigns cannot receive new applications (Enforced server-side).
     */
    public function test_completed_campaign_cannot_receive_new_applications(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandAProfile->id,
            'title' => 'حملة مكتملة ومغلقة',
            'slug' => 'completed-camp',
            'category' => 'أزياء وموضة',
            'category_slug' => 'fashion',
            'description' => 'وصف',
            'brief' => 'موجز',
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15%',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['كود خصم'],
            'status' => CampaignStatus::COMPLETED,
        ]);

        $response = $this->actingAs($this->marketerUser)
            ->post(route('marketer.campaigns.apply', $campaign), [
                'pitch_note' => 'محاولة تقديم على حملة مكتملة',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('campaign_applications', [
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
        ]);
    }

    /**
     * 10. Cross-brand isolation: Brand A cannot view, edit, or approve Brand B's campaign/applicants.
     */
    public function test_cross_brand_authorization_remains_blocked(): void
    {
        $brandBCampaign = Campaign::create([
            'brand_profile_id' => $this->brandBProfile->id,
            'title' => 'حملة متجر ب الحصرية',
            'slug' => 'brand-b-exclusive',
            'category' => 'إلكترونيات',
            'category_slug' => 'electronics',
            'description' => 'وصف',
            'brief' => 'موجز',
            'target_markets' => ['مصر'],
            'currencies' => ['EGP'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '10%',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['كود خصم'],
            'status' => CampaignStatus::ACTIVE,
        ]);

        $application = CampaignApplication::create([
            'campaign_id' => $brandBCampaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'status' => CampaignApplicationStatus::PENDING,
            'applied_at' => now(),
        ]);

        // Brand A attempts to view Brand B's applications
        $responseViewApps = $this->actingAs($this->brandAUser)
            ->get(route('brand.campaigns.applications', $brandBCampaign));
        $responseViewApps->assertStatus(403);

        // Brand A attempts to approve Brand B's applicant
        $responseApprove = $this->actingAs($this->brandAUser)
            ->post(route('brand.campaigns.applications.review', [$brandBCampaign, $application]), [
                'action' => 'approve',
                'tracking_code' => 'HACKED-CODE',
            ]);
        $responseApprove->assertStatus(403);

        $application->refresh();
        $this->assertEquals(CampaignApplicationStatus::PENDING, $application->status);
        $this->assertNull($application->tracking_code);
    }
}
