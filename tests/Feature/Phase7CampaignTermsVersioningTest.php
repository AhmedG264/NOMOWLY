<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\CampaignApplicationStatus;
use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Enums\CampaignTermsChangeRequestStatus;
use App\Enums\CampaignTermsChangeScope;
use App\Enums\CampaignTermsVersionStatus;
use App\Enums\ConversionStatus;
use App\Enums\Currency;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AttributionClick;
use App\Models\AuditLog;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\CampaignTermsChangeRequest;
use App\Models\CampaignTermsVersion;
use App\Models\Conversion;
use App\Models\MarketerProfile;
use App\Models\User;
use App\Services\AttributionService;
use App\Services\Campaign\CampaignTermsService;
use App\Services\Ledger\CommissionSettlementService;
use App\Services\Ledger\WalletService;
use App\Services\Math\MoneyMath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase7CampaignTermsVersioningTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $brandUser;
    protected BrandProfile $brandProfile;
    protected User $otherBrandUser;
    protected BrandProfile $otherBrandProfile;
    protected User $marketerUser;
    protected MarketerProfile $marketerProfile;
    protected CampaignTermsService $termsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->termsService = app(CampaignTermsService::class);

        // 1. Super Admin
        $this->superAdmin = User::create([
            'name' => 'المدير العام للمنصة',
            'email' => 'admin@nomowly.com',
            'password' => Hash::make('AdminPass123!'),
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        // 2. Primary Brand User
        $this->brandUser = User::create([
            'name' => 'العلامة التجارية الأولى',
            'email' => 'brand1@nomowly.com',
            'password' => Hash::make('BrandPass123!'),
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->brandProfile = BrandProfile::create([
            'user_id' => $this->brandUser->id,
            'company_name' => 'شركة رداء النخبة',
            'currency' => 'SAR',
        ]);

        // 3. Other Brand User
        $this->otherBrandUser = User::create([
            'name' => 'العلامة التجارية الثانية',
            'email' => 'brand2@nomowly.com',
            'password' => Hash::make('BrandPass123!'),
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->otherBrandProfile = BrandProfile::create([
            'user_id' => $this->otherBrandUser->id,
            'company_name' => 'متجر أوريغون',
            'currency' => 'SAR',
        ]);

        // 4. Marketer User
        $this->marketerUser = User::create([
            'name' => 'المسوق المحترف',
            'email' => 'marketer@nomowly.com',
            'password' => Hash::make('MarketerPass123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->marketerProfile = MarketerProfile::create([
            'user_id' => $this->marketerUser->id,
            'full_name' => 'عمر التميمي',
            'country' => 'SA',
        ]);
    }

    /**
     * Helper to create a base campaign
     */
    protected function createCampaign(array $overrides = []): Campaign
    {
        $campaign = Campaign::create(array_merge([
            'brand_profile_id' => $this->brandProfile->id,
            'title' => 'حملة الصيف الترويجية 2026',
            'slug' => 'summer-promo-2026',
            'category' => 'أزياء وموضة',
            'category_slug' => 'fashion',
            'description' => 'حملة أزياء موسمية للصيف بتخفيضات وعمولات مجزية.',
            'brief' => 'شروط الحملة وتفاصيل المحتوى المطلوب.',
            'target_markets' => ['السعودية', 'الإمارات'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15.00',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['كود خصم حصري', 'رابط تتبع ذكي'],
            'status' => CampaignStatus::ACTIVE,
        ], $overrides));

        $this->termsService->createInitialVersion($campaign, $this->brandUser);

        return $campaign->fresh();
    }

    /**
     * 1. Test campaign creation automatically generates initial terms version v1
     */
    public function test_campaign_creation_automatically_generates_initial_terms_version_v1(): void
    {
        $this->actingAs($this->brandUser);

        $response = $this->post(route('brand.campaigns.store'), [
            'title' => 'حملة العطور الفاخرة',
            'category' => 'عطور وتجميل',
            'description' => 'وصف الحملة العطرية',
            'brief' => 'ملخص متطلبات الحملة',
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO->value,
            'commission_value' => '20.00',
            'cookie_duration_days' => 45,
            'tracking_methods' => ['كود خصم حصري'],
        ]);

        $response->assertRedirect(route('brand.campaigns.index'));

        $campaign = Campaign::where('title', 'حملة العطور الفاخرة')->first();
        $this->assertNotNull($campaign);

        // Assert v1 terms version exists
        $version = $campaign->currentTermsVersion;
        $this->assertNotNull($version);
        $this->assertEquals(1, $version->version_number);
        $this->assertEquals(CampaignTermsVersionStatus::ACTIVE, $version->status);
        $this->assertEquals(CampaignCommissionType::PERCENTAGE_CPO, $version->commission_type);
        $this->assertEquals('20.00', $version->commission_value);
        $this->assertEquals(45, $version->cookie_duration_days);
        $this->assertEquals('SAR', $version->currency);

        // Campaign is unlocked initially
        $this->assertFalse($campaign->is_terms_locked);

        // AuditLog verified
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CAMPAIGN_TERMS_VERSION_CREATED->value,
            'entity_type' => 'CampaignTermsVersion',
            'entity_id' => $version->id,
        ]);
    }

    /**
     * 2. Test terms can be directly updated before any marketer joins or activity is recorded
     */
    public function test_terms_can_be_directly_updated_before_any_marketer_joins_or_activity_recorded(): void
    {
        $this->actingAs($this->brandUser);
        $campaign = $this->createCampaign();

        $this->assertFalse($campaign->isTermsLocked());

        // Update campaign including commission and cookie days
        $response = $this->put(route('brand.campaigns.update', $campaign), [
            'title' => 'حملة الصيف الترويجية 2026 - محدثة',
            'category' => 'أزياء وموضة',
            'description' => 'وصف محدث',
            'brief' => 'ملخص محدث',
            'target_markets' => ['السعودية', 'الإمارات', 'الكويت'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO->value,
            'commission_value' => '25.00', // Changed from 15 to 25
            'cookie_duration_days' => 60,   // Changed from 30 to 60
            'tracking_methods' => ['كود خصم حصري'],
            'status' => CampaignStatus::ACTIVE->value,
        ]);

        $response->assertRedirect(route('brand.campaigns.index'));

        $campaign->refresh();
        $this->assertEquals('25.00', $campaign->commission_value);
        $this->assertEquals(60, $campaign->cookie_duration_days);

        // Assert v1 was synced cleanly
        $version = $campaign->currentTermsVersion;
        $this->assertEquals('25.00', $version->commission_value);
        $this->assertEquals(60, $version->cookie_duration_days);
        $this->assertFalse($campaign->is_terms_locked);
    }

    /**
     * 3. Test approving first marketer automatically locks campaign terms
     */
    public function test_approving_first_marketer_automatically_locks_campaign_terms(): void
    {
        $campaign = $this->createCampaign();
        $this->assertFalse($campaign->is_terms_locked);

        // Marketer applies
        $this->actingAs($this->marketerUser);
        $this->post(route('marketer.campaigns.apply', $campaign), [
            'pitch_note' => 'أود الترويج عبر منصة تيك توك وإنستغرام',
        ]);

        $application = CampaignApplication::where('campaign_id', $campaign->id)
            ->where('marketer_profile_id', $this->marketerProfile->id)
            ->first();
        $this->assertNotNull($application);
        $this->assertEquals($campaign->current_terms_version_id, $application->terms_version_id);

        // Brand approves application
        $this->actingAs($this->brandUser);
        $response = $this->post(route('brand.campaigns.applications.review', [$campaign, $application]), [
            'action' => 'approve',
            'tracking_code' => 'SUMMER-KAMAL-15',
        ]);

        $response->assertSessionHasNoErrors();
        $campaign->refresh();

        // Must now be strictly locked!
        $this->assertTrue($campaign->is_terms_locked);
        $this->assertNotNull($campaign->terms_locked_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CAMPAIGN_TERMS_LOCKED->value,
            'entity_type' => 'Campaign',
            'entity_id' => $campaign->id,
        ]);
    }

    /**
     * 4. Test recording first click or conversion automatically locks terms
     */
    public function test_recording_first_click_or_conversion_automatically_locks_terms(): void
    {
        $campaign = $this->createCampaign();
        $this->assertFalse($campaign->is_terms_locked);

        // Create application
        $application = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'terms_version_id' => $campaign->current_terms_version_id,
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'CLICK-LOCK-CODE',
        ]);

        // AttributionService records click
        $request = \Illuminate\Http\Request::create('/track/click/CLICK-LOCK-CODE', 'GET');
        $attributionService = app(AttributionService::class);
        $result = $attributionService->recordClick($request, 'CLICK-LOCK-CODE');

        $this->assertTrue($result['success']);
        $campaign->refresh();

        // Must now be locked
        $this->assertTrue($campaign->is_terms_locked);
        $this->assertNotNull($result['click']->terms_version_id);
        $this->assertEquals($campaign->current_terms_version_id, $result['click']->terms_version_id);
    }

    /**
     * 5. Test attempting to directly modify financial terms after lock is strictly rejected
     */
    public function test_attempting_to_directly_modify_financial_terms_after_lock_is_strictly_rejected(): void
    {
        $campaign = $this->createCampaign();
        $campaign->lockTerms('قفل تجريبي');
        $this->assertTrue($campaign->isTermsLocked());

        $this->actingAs($this->brandUser);

        // Attempt direct modification of commission_value from 15.00 to 5.00
        $response = $this->put(route('brand.campaigns.update', $campaign), [
            'title' => 'تعديل عنوان الحملة',
            'category' => 'أزياء وموضة',
            'description' => 'وصف عادي',
            'brief' => 'ملخص عادي',
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO->value,
            'commission_value' => '5.00', // Modified!
            'cookie_duration_days' => 30,
            'tracking_methods' => ['كود خصم حصري'],
            'status' => CampaignStatus::ACTIVE->value,
        ]);

        $response->assertSessionHasErrors('terms');
        $campaign->refresh();

        // Must preserve the original commission value!
        $this->assertEquals('15.00', $campaign->commission_value);
    }

    /**
     * 6. Test non-contractual details can still be updated when locked
     */
    public function test_non_contractual_details_can_still_be_updated_when_locked(): void
    {
        $campaign = $this->createCampaign();
        $campaign->lockTerms('قفل تجريبي');

        $this->actingAs($this->brandUser);

        // Update non-contractual fields while keeping financial terms intact
        $response = $this->put(route('brand.campaigns.update', $campaign), [
            'title' => 'عنوان محدث بدون لمس العمولات',
            'category' => 'أزياء وموضة',
            'description' => 'وصف تسويقي جديد وجذاب للمنتجات',
            'brief' => 'ملخص جديد للمحتوى',
            'target_markets' => ['السعودية', 'الإمارات'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO->value,
            'commission_value' => '15.00', // Same as locked
            'cookie_duration_days' => 30,   // Same as locked
            'tracking_methods' => ['كود خصم حصري'],
            'brand_guidelines' => 'إرشادات جديدة تماماً',
            'status' => CampaignStatus::ACTIVE->value,
        ]);

        $response->assertRedirect(route('brand.campaigns.index'));
        $campaign->refresh();

        $this->assertEquals('عنوان محدث بدون لمس العمولات', $campaign->title);
        $this->assertEquals('إرشادات جديدة تماماً', $campaign->brand_guidelines);
        $this->assertEquals('15.00', $campaign->commission_value);
    }

    /**
     * 7. Test brand can create campaign terms change request with diff
     */
    public function test_brand_can_create_campaign_terms_change_request_with_diff(): void
    {
        $campaign = $this->createCampaign();
        $campaign->lockTerms('قفل تجريبي');

        $this->actingAs($this->brandUser);

        $response = $this->post(route('brand.campaigns.change-requests.store', $campaign), [
            'reason' => 'موسم الجمعة البيضاء يتطلب رفع العمولة لتحفيز صناع المحتوى',
            'scope' => CampaignTermsChangeScope::NEW_MARKETERS_ONLY->value,
            'proposed_effective_from' => now()->addDays(2)->toDateTimeString(),
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO->value,
            'commission_value' => '22.50', // Proposed increase from 15 to 22.50
            'cookie_duration_days' => 45,   // Proposed increase from 30 to 45
            'currency' => 'SAR',
        ]);

        $response->assertRedirect(route('brand.campaigns.terms', $campaign));

        $changeRequest = CampaignTermsChangeRequest::where('campaign_id', $campaign->id)->first();
        $this->assertNotNull($changeRequest);
        $this->assertEquals(CampaignTermsChangeRequestStatus::PENDING, $changeRequest->status);
        $this->assertEquals(CampaignTermsChangeScope::NEW_MARKETERS_ONLY, $changeRequest->scope);
        $this->assertEquals('موسم الجمعة البيضاء يتطلب رفع العمولة لتحفيز صناع المحتوى', $changeRequest->reason);

        // Check Diff Summary structure
        $diff = $changeRequest->diff_summary;
        $this->assertArrayHasKey('commission_value', $diff);
        $this->assertEquals('15.00', $diff['commission_value']['old']);
        $this->assertEquals('22.50', $diff['commission_value']['new']);

        $this->assertArrayHasKey('cookie_duration_days', $diff);
        $this->assertEquals('30', $diff['cookie_duration_days']['old']);
        $this->assertEquals('45', $diff['cookie_duration_days']['new']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CAMPAIGN_TERMS_CHANGE_REQUESTED->value,
            'entity_type' => 'CampaignTermsChangeRequest',
            'entity_id' => $changeRequest->id,
        ]);
    }

    /**
     * 8. Test unauthorized users and other brands cannot create change requests
     */
    public function test_unauthorized_users_and_other_brands_cannot_create_change_requests(): void
    {
        $campaign = $this->createCampaign();

        // Marketer attempts to create change request
        $this->actingAs($this->marketerUser);
        $response = $this->post(route('brand.campaigns.change-requests.store', $campaign), [
            'reason' => 'محاولة غير مصرح بها',
            'scope' => CampaignTermsChangeScope::NEW_MARKETERS_ONLY->value,
            'commission_value' => '50.00',
        ]);
        $response->assertStatus(403);

        // Other brand attempts to create change request for Campaign belonging to Brand A
        $this->actingAs($this->otherBrandUser);
        $response = $this->post(route('brand.campaigns.change-requests.store', $campaign), [
            'reason' => 'محاولة علامة أخرى تعديل حملة لا تملكها',
            'scope' => CampaignTermsChangeScope::NEW_MARKETERS_ONLY->value,
            'commission_value' => '50.00',
        ]);
        $response->assertStatus(403);
    }

    /**
     * 9. Test admin can review and approve change request creating new terms version
     */
    public function test_admin_can_review_and_approve_change_request_creating_new_terms_version(): void
    {
        $campaign = $this->createCampaign();
        $initialVersion = $campaign->currentTermsVersion;

        // Create change request
        $changeRequest = $this->termsService->createChangeRequest(
            $campaign,
            [
                'commission_type' => CampaignCommissionType::PERCENTAGE_CPO->value,
                'commission_value' => '25.00',
                'cookie_duration_days' => 45,
                'currency' => 'SAR',
            ],
            'رفع العمولة للربع الأخير من العام',
            CampaignTermsChangeScope::NEW_MARKETERS_ONLY->value,
            now()->addDay()->toDateTimeString(),
            $this->brandUser
        );

        $this->actingAs($this->superAdmin);

        // Admin approves
        $response = $this->post(route('admin.campaigns.change-requests.approve', $changeRequest), [
            'reviewer_notes' => 'تمت المراجعة والاعتماد وفق سياسة المنصة',
        ]);

        $response->assertRedirect(route('admin.campaigns.change-requests.show', $changeRequest));

        $changeRequest->refresh();
        $this->assertEquals(CampaignTermsChangeRequestStatus::APPROVED, $changeRequest->status);
        $this->assertEquals($this->superAdmin->id, $changeRequest->reviewed_by_user_id);
        $this->assertEquals('تمت المراجعة والاعتماد وفق سياسة المنصة', $changeRequest->reviewer_notes);

        // Check new version (v2) created
        $newVersion = $changeRequest->appliedVersion;
        $this->assertNotNull($newVersion);
        $this->assertEquals(2, $newVersion->version_number);
        $this->assertEquals(CampaignTermsVersionStatus::ACTIVE, $newVersion->status);
        $this->assertEquals('25.00', $newVersion->commission_value);
        $this->assertEquals(45, $newVersion->cookie_duration_days);

        // Check old version superseded
        $initialVersion->refresh();
        $this->assertEquals(CampaignTermsVersionStatus::SUPERSEDED, $initialVersion->status);
        $this->assertNotNull($initialVersion->effective_until);

        // Check campaign updated to point to v2
        $campaign->refresh();
        $this->assertEquals($newVersion->id, $campaign->current_terms_version_id);
        $this->assertEquals('25.00', $campaign->commission_value);
        $this->assertEquals(45, $campaign->cookie_duration_days);

        // AuditLogs
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CAMPAIGN_TERMS_CHANGE_APPROVED->value,
            'entity_id' => $changeRequest->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CAMPAIGN_TERMS_VERSION_CREATED->value,
            'entity_id' => $newVersion->id,
        ]);
    }

    /**
     * 10. Test admin can reject change request with mandatory reason
     */
    public function test_admin_can_reject_change_request_with_mandatory_reason(): void
    {
        $campaign = $this->createCampaign();

        $changeRequest = $this->termsService->createChangeRequest(
            $campaign,
            [
                'commission_value' => '1.00', // Unreasonably low commission
            ],
            'تخفيض غير مبرر للعمولة',
            CampaignTermsChangeScope::NEW_MARKETERS_ONLY->value,
            null,
            $this->brandUser
        );

        $this->actingAs($this->superAdmin);

        // Admin rejects
        $response = $this->post(route('admin.campaigns.change-requests.reject', $changeRequest), [
            'reviewer_notes' => 'النسبة المقترحة منخفضة جداً وتتعارض مع معايير جودة الحملات في نمولي.',
        ]);

        $response->assertRedirect(route('admin.campaigns.change-requests.show', $changeRequest));

        $changeRequest->refresh();
        $this->assertEquals(CampaignTermsChangeRequestStatus::REJECTED, $changeRequest->status);
        $this->assertEquals('النسبة المقترحة منخفضة جداً وتتعارض مع معايير جودة الحملات في نمولي.', $changeRequest->reviewer_notes);

        // Ensure no new version was created
        $this->assertEquals(1, $campaign->termsVersions()->count());
        $this->assertEquals('15.00', $campaign->fresh()->commission_value);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CAMPAIGN_TERMS_CHANGE_REJECTED->value,
            'status' => 'REJECTED',
        ]);
    }

    /**
     * 11. Test new marketers receive v2 while existing marketers retain v1 under NEW_MARKETERS_ONLY scope
     */
    public function test_new_marketers_receive_v2_while_existing_marketers_retain_v1_under_new_marketers_only(): void
    {
        $campaign = $this->createCampaign();
        $v1 = $campaign->currentTermsVersion;

        // 1. Marketer 1 joins under v1
        $app1 = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'terms_version_id' => $v1->id,
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'MARKETER1-V1',
            'accepted_terms_at' => now(),
        ]);
        $campaign->lockTerms('قفل المسوق الأول');

        // 2. Terms upgrade to v2 under NEW_MARKETERS_ONLY
        $cr = $this->termsService->createChangeRequest(
            $campaign,
            ['commission_value' => '30.00'],
            'ترقية الشروط للمسوقين الجدد',
            CampaignTermsChangeScope::NEW_MARKETERS_ONLY->value,
            null,
            $this->brandUser
        );
        $v2 = $this->termsService->approveChangeRequest($cr, $this->superAdmin, 'معتمد');

        // 3. Create second marketer
        $marketerUser2 = User::create([
            'name' => 'مسوق جديد 2',
            'email' => 'marketer2@nomowly.com',
            'password' => Hash::make('Pass123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);
        $marketerProfile2 = MarketerProfile::create([
            'user_id' => $marketerUser2->id,
            'full_name' => 'سعد المنصور',
        ]);

        $this->actingAs($marketerUser2);
        $this->post(route('marketer.campaigns.apply', $campaign), [
            'pitch_note' => 'طلب مسوق جديد',
        ]);

        $app2 = CampaignApplication::where('campaign_id', $campaign->id)
            ->where('marketer_profile_id', $marketerProfile2->id)
            ->first();

        // Assert Marketer 1 remains bound to v1
        $app1->refresh();
        $this->assertEquals($v1->id, $app1->terms_version_id);
        $this->assertEquals('15.00', $app1->termsVersion->commission_value);

        // Assert Marketer 2 is bound to v2
        $this->assertEquals($v2->id, $app2->terms_version_id);
        $this->assertEquals('30.00', $app2->termsVersion->commission_value);
    }

    /**
     * 12. Test conversion records accurate terms snapshot and calculates commission from correct version
     */
    public function test_conversion_records_accurate_terms_snapshot_and_calculates_commission_from_correct_version(): void
    {
        $campaign = $this->createCampaign();
        $v1 = $campaign->currentTermsVersion;

        // Upgrade to v2 (20%)
        $cr = $this->termsService->createChangeRequest(
            $campaign,
            ['commission_value' => '20.00'],
            'ترقية العمولة إلى 20%',
            CampaignTermsChangeScope::NEW_MARKETERS_ONLY->value,
            null,
            $this->brandUser
        );
        $v2 = $this->termsService->approveChangeRequest($cr, $this->superAdmin);

        // Marketer A bound to v1 (15%)
        $appA = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'terms_version_id' => $v1->id,
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'CODE-V1-USER',
        ]);

        // Click recorded for Marketer A
        $clickA = AttributionClick::create([
            'campaign_id' => $campaign->id,
            'campaign_application_id' => $appA->id,
            'terms_version_id' => $v1->id,
            'tracking_code' => 'CODE-V1-USER',
            'visitor_id' => 'vid_user_v1',
            'session_id' => 'sid_user_v1',
            'created_at' => now(),
        ]);

        // Conversion for Marketer A (Order value: 1,000 SAR)
        $attributionService = app(AttributionService::class);
        $convA = $attributionService->recordConversion([
            'campaign' => $campaign,
            'external_order_id' => 'ORDER-V1-1001',
            'order_value' => 1000.00,
            'currency' => 'SAR',
            'visitor_id' => 'vid_user_v1',
        ]);

        // Marketer A commission must be 15% = 150.00 SAR
        $this->assertEquals('150.00', (string) $convA->commission_value);
        $this->assertEquals($v1->id, $convA->terms_version_id);

        $snapshotA = $convA->commission_terms_snapshot;
        $this->assertIsArray($snapshotA);
        $this->assertEquals(1, $snapshotA['version_number']);
        $this->assertEquals('15.00', $snapshotA['commission_value']);
        $this->assertEquals('150', $snapshotA['calculated_commission']);

        // Marketer B bound to v2 (20%)
        $marketerUserB = User::create([
            'name' => 'مسوق ب',
            'email' => 'mb@nomowly.com',
            'password' => Hash::make('Pass123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);
        $marketerProfileB = MarketerProfile::create([
            'user_id' => $marketerUserB->id,
            'full_name' => 'مسوق ب',
        ]);

        $appB = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $marketerProfileB->id,
            'terms_version_id' => $v2->id,
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'CODE-V2-USER',
        ]);

        $clickB = AttributionClick::create([
            'campaign_id' => $campaign->id,
            'campaign_application_id' => $appB->id,
            'terms_version_id' => $v2->id,
            'tracking_code' => 'CODE-V2-USER',
            'visitor_id' => 'vid_user_v2',
            'session_id' => 'sid_user_v2',
            'created_at' => now(),
        ]);

        $convB = $attributionService->recordConversion([
            'campaign' => $campaign,
            'external_order_id' => 'ORDER-V2-2002',
            'order_value' => 1000.00,
            'currency' => 'SAR',
            'visitor_id' => 'vid_user_v2',
        ]);

        // Marketer B commission must be 20% = 200.00 SAR
        $this->assertEquals('200.00', (string) $convB->commission_value);
        $this->assertEquals($v2->id, $convB->terms_version_id);

        $snapshotB = $convB->commission_terms_snapshot;
        $this->assertEquals(2, $snapshotB['version_number']);
        $this->assertEquals('20.00', $snapshotB['commission_value']);
        $this->assertEquals('200', $snapshotB['calculated_commission']);
    }

    /**
     * 13. Test historical conversions never mutate when campaign terms upgrade later
     */
    public function test_historical_conversions_never_mutate_when_campaign_terms_upgrade_later(): void
    {
        $campaign = $this->createCampaign();
        $v1 = $campaign->currentTermsVersion;

        $app = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'terms_version_id' => $v1->id,
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'HISTORICAL-CODE',
        ]);

        $attributionService = app(AttributionService::class);
        $conv = $attributionService->recordConversion([
            'campaign' => $campaign,
            'external_order_id' => 'HIST-ORDER-99',
            'order_value' => 500.00,
            'currency' => 'SAR',
            'tracking_code' => 'HISTORICAL-CODE',
        ]);

        $initialCommission = (string) $conv->commission_value;
        $this->assertEquals('75.00', $initialCommission); // 15% of 500 = 75

        // Now upgrade campaign terms to v2 (35%)
        $cr = $this->termsService->createChangeRequest(
            $campaign,
            ['commission_value' => '35.00'],
            'تحديث كبير للعمولات',
            CampaignTermsChangeScope::NEW_MARKETERS_ONLY->value,
            null,
            $this->brandUser
        );
        $v2 = $this->termsService->approveChangeRequest($cr, $this->superAdmin);

        // And upgrade again to v3 (50%)
        $cr2 = $this->termsService->createChangeRequest(
            $campaign,
            ['commission_value' => '50.00'],
            'تحديث إضافي',
            CampaignTermsChangeScope::NEW_MARKETERS_ONLY->value,
            null,
            $this->brandUser
        );
        $v3 = $this->termsService->approveChangeRequest($cr2, $this->superAdmin);

        $conv->refresh();

        // Historical conversion MUST retain exact initial values and snapshot!
        $this->assertEquals('75.00', (string) $conv->commission_value);
        $this->assertEquals($v1->id, $conv->terms_version_id);
        $this->assertEquals(1, $conv->commission_terms_snapshot['version_number']);
        $this->assertEquals('15.00', $conv->commission_terms_snapshot['commission_value']);
    }

    /**
     * 14. Test financial ledger settlement is fully compatible and uses immutable conversion snapshot
     */
    public function test_financial_ledger_settlement_is_fully_compatible_and_uses_immutable_conversion_snapshot(): void
    {
        $campaign = $this->createCampaign();

        // 1. Fund Brand Escrow with 1,000 SAR
        $walletService = app(WalletService::class);
        $brandWallet = $walletService->getOrCreateWallet($this->brandUser, Currency::SAR);
        $brandWallet->update([
            'balance' => '1000.0000',
            'reserved_balance' => '1000.0000',
        ]);

        // 2. Create approved application
        $app = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'terms_version_id' => $campaign->current_terms_version_id,
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'SETTLE-TEST-CODE',
        ]);

        // 3. Record conversion under v1
        $attributionService = app(AttributionService::class);
        $conv = $attributionService->recordConversion([
            'campaign' => $campaign,
            'external_order_id' => 'SETTLE-ORDER-88',
            'order_value' => 800.00, // 15% = 120.00 SAR
            'currency' => 'SAR',
            'tracking_code' => 'SETTLE-TEST-CODE',
        ]);
        $conv->update(['status' => ConversionStatus::APPROVED]);

        // 4. In the meantime, campaign terms upgrade to v2 (30%)
        $cr = $this->termsService->createChangeRequest(
            $campaign,
            ['commission_value' => '30.00'],
            'تغيير شروط لاحق',
            CampaignTermsChangeScope::NEW_MARKETERS_ONLY->value,
            null,
            $this->brandUser
        );
        $this->termsService->approveChangeRequest($cr, $this->superAdmin);

        // 5. Settle Conversion
        $settlementService = app(CommissionSettlementService::class);
        $settlement = $settlementService->settleConversion($conv, $this->superAdmin);

        $this->assertNotNull($settlement);
        $this->assertEquals('120.0000', MoneyMath::format($settlement->commission_amount));

        // Ledger transaction is balanced
        $tx = $settlement->ledgerTransaction;
        $this->assertTrue($tx->isBalanced());

        // Escrow deducted by 120.00, Marketer available credited by 120.00
        $marketerWallet = $walletService->getOrCreateWallet($this->marketerUser, Currency::SAR);
        $this->assertEquals('120.0000', MoneyMath::format($marketerWallet->available_balance));
        $this->assertEquals('880.0000', MoneyMath::format($brandWallet->fresh()->reserved_balance));
    }

    /**
     * 15. Test marketer can explicitly accept new terms version under consent scope
     */
    public function test_marketer_can_explicitly_accept_new_terms_version_under_consent_scope(): void
    {
        $campaign = $this->createCampaign();
        $v1 = $campaign->currentTermsVersion;

        $app = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'terms_version_id' => $v1->id,
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'CONSENT-CODE',
            'accepted_terms_at' => now()->subDays(5),
        ]);

        // Campaign upgrades to v2 with ALL_MARKETERS_WITH_CONSENT
        $cr = $this->termsService->createChangeRequest(
            $campaign,
            ['commission_value' => '25.00'],
            'عرض جديد مع اشتراط موافقة المسوق',
            CampaignTermsChangeScope::ALL_MARKETERS_WITH_CONSENT->value,
            null,
            $this->brandUser
        );
        $v2 = $this->termsService->approveChangeRequest($cr, $this->superAdmin);

        $this->actingAs($this->marketerUser);

        // Marketer accepts v2
        $response = $this->post(route('marketer.campaigns.accept-terms', [$app, $v2]));
        $response->assertSessionHas('status');

        $app->refresh();
        $this->assertEquals($v2->id, $app->terms_version_id);
        $this->assertNotNull($app->accepted_terms_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CAMPAIGN_TERMS_ACCEPTED_BY_MARKETER->value,
            'entity_type' => 'CampaignApplication',
            'entity_id' => $app->id,
        ]);
    }

    /**
     * 16. Test admin and brand views render terms and diffs without error
     */
    public function test_admin_and_brand_views_render_terms_and_diffs_without_error(): void
    {
        $campaign = $this->createCampaign();
        $cr = $this->termsService->createChangeRequest(
            $campaign,
            ['commission_value' => '28.00'],
            'سبب تجريبي للاختبار',
            CampaignTermsChangeScope::NEW_MARKETERS_ONLY->value,
            null,
            $this->brandUser
        );

        // 1. Brand Terms Page
        $this->actingAs($this->brandUser);
        $response = $this->get(route('brand.campaigns.terms', $campaign));
        $response->assertOk();
        $response->assertSee('سجل الإصدارات التعاقدية للحملة');
        $response->assertSee('الإصدار v1');

        // 2. Brand Create Change Request Page
        $response = $this->get(route('brand.campaigns.change-requests.create', $campaign));
        $response->assertOk();
        $response->assertSee('تقديم طلب تغيير شروط الحملة');

        // 3. Admin Change Requests Index
        $this->actingAs($this->superAdmin);
        $response = $this->get(route('admin.campaigns.change-requests.index'));
        $response->assertOk();
        $response->assertSee('طلبات تعديل شروط الحملات التسويقية');
        $response->assertSee($campaign->title);

        // 4. Admin Change Request Detail View with Diff
        $response = $this->get(route('admin.campaigns.change-requests.show', $cr));
        $response->assertOk();
        $response->assertSee('مقارنة الفروقات بين الشروط السارية والشروط المقترحة');
        $response->assertSee('28.00');
    }
}
