<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\CampaignApplicationStatus;
use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Enums\MarketerSpecializationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\MarketerProfile;
use App\Models\MarketerSpecialization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4CampaignManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $brandUser;
    protected BrandProfile $brandProfile;
    protected User $otherBrandUser;
    protected BrandProfile $otherBrandProfile;
    protected User $marketerUser;
    protected MarketerProfile $marketerProfile;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Primary Brand User
        $this->brandUser = User::factory()->create([
            'email' => 'brand1@nomowly.com',
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $this->brandProfile = BrandProfile::create([
            'user_id' => $this->brandUser->id,
            'company_name' => 'Sara Couture',
            'currency' => 'SAR',
        ]);

        // 2. Create Secondary Brand User (For authorization isolation testing)
        $this->otherBrandUser = User::factory()->create([
            'email' => 'brand2@nomowly.com',
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $this->otherBrandProfile = BrandProfile::create([
            'user_id' => $this->otherBrandUser->id,
            'company_name' => 'Other Brand',
            'currency' => 'AED',
        ]);

        // 3. Create Marketer User
        $this->marketerUser = User::factory()->create([
            'email' => 'marketer1@nomowly.com',
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $this->marketerProfile = MarketerProfile::create([
            'user_id' => $this->marketerUser->id,
            'full_name' => 'أحمد كمال',
            'country' => 'السعودية',
        ]);
        MarketerSpecialization::create([
            'marketer_profile_id' => $this->marketerProfile->id,
            'specialization' => MarketerSpecializationType::CREATOR,
            'is_primary' => true,
        ]);

        // 4. Create Super Admin
        $this->superAdmin = User::factory()->create([
            'email' => 'admin@nomowly.com',
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
    }

    /**
     * Test Campaign domain entity creation, relationships, and scopes.
     */
    public function test_campaign_domain_model_and_relationships(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfile->id,
            'title' => 'حملة أزياء العيد الفاخرة',
            'slug' => 'eid-fashion-collection',
            'category' => 'أزياء وموضة',
            'category_slug' => 'fashion',
            'description' => 'وصف تجريبي للحملة مع تفاصيل حصرية.',
            'brief' => 'شرح تفصيلي للحملة والجمهور المستهدف.',
            'target_markets' => ['السعودية', 'الإمارات'],
            'currencies' => ['SAR', 'AED'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15%',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['كود خصم حصري'],
            'status' => CampaignStatus::ACTIVE,
            'is_featured' => true,
        ]);

        $this->assertNotNull($campaign->id);
        $this->assertEquals('Sara Couture', $campaign->brandProfile->company_name);
        $this->assertTrue($this->brandProfile->campaigns->contains($campaign));
        $this->assertEquals(CampaignStatus::ACTIVE, $campaign->status);
        $this->assertEquals(CampaignCommissionType::PERCENTAGE_CPO, $campaign->commission_type);
    }

    /**
     * Test public marketplace queries database campaigns with filters and search.
     */
    public function test_public_marketplace_displays_active_campaigns_and_filters(): void
    {
        Campaign::create([
            'brand_profile_id' => $this->brandProfile->id,
            'title' => 'عطور لافندر الشرقية الفاخرة',
            'slug' => 'lavender-perfumes',
            'category' => 'عطور وتجميل',
            'category_slug' => 'perfumes',
            'description' => 'عطور فرنسية وشرقية مميزة.',
            'brief' => 'موجز تفصيلي للعطور.',
            'target_markets' => ['السعودية', 'الإمارات'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::FIXED_PER_ORDER,
            'commission_value' => '50 ر.س',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['كود خصم حصري'],
            'status' => CampaignStatus::ACTIVE,
        ]);

        Campaign::create([
            'brand_profile_id' => $this->otherBrandProfile->id,
            'title' => 'أجهزة إلكترونية ذكية في مصر',
            'slug' => 'tech-gadgets-egypt',
            'category' => 'إلكترونيات',
            'category_slug' => 'electronics',
            'description' => 'سماعات وشواحن معتمدة.',
            'brief' => 'موجز الإلكترونيات.',
            'target_markets' => ['مصر'],
            'currencies' => ['EGP'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '10%',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['رابط تتبع ذكي'],
            'status' => CampaignStatus::ACTIVE,
        ]);

        // 1. Visit marketplace without filters -> both are visible
        $response = $this->get(route('marketplace'));
        $response->assertStatus(200);
        $response->assertSee('عطور لافندر الشرقية الفاخرة');
        $response->assertSee('أجهزة إلكترونية ذكية في مصر');

        // 2. Filter by Category = perfumes
        $categoryResponse = $this->get(route('marketplace', ['category' => 'perfumes']));
        $categoryResponse->assertStatus(200);
        $categoryResponse->assertSee('عطور لافندر الشرقية الفاخرة');
        $categoryResponse->assertDontSee('أجهزة إلكترونية ذكية في مصر');

        // 3. Filter by Country = مصر
        $countryResponse = $this->get(route('marketplace', ['country' => 'مصر']));
        $countryResponse->assertStatus(200);
        $countryResponse->assertSee('أجهزة إلكترونية ذكية في مصر');
        $countryResponse->assertDontSee('عطور لافندر الشرقية الفاخرة');

        // 4. Search query
        $searchResponse = $this->get(route('marketplace', ['q' => 'لافندر']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('عطور لافندر الشرقية الفاخرة');
        $searchResponse->assertDontSee('أجهزة إلكترونية ذكية في مصر');
    }

    /**
     * Test single campaign detail view renders all critical information.
     */
    public function test_campaign_detail_page_is_publicly_viewable(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfile->id,
            'title' => 'كوليكشن عبايات سارة الصيفية',
            'slug' => 'sara-summer-collection',
            'category' => 'أزياء وموضة',
            'category_slug' => 'fashion',
            'description' => 'أزياء صيفية خفيفة وأنيقة.',
            'brief' => 'موجز تفصيلي كامل عن العبايات وتاريخ العلامة.',
            'requirements' => 'فيديو ريلز مدته 30 ثانية مع تجربة ارتداء.',
            'target_markets' => ['السعودية', 'الكويت'],
            'currencies' => ['SAR', 'KWD'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15%',
            'avg_basket' => '450 ر.س',
            'conversion_rate' => '4.8%',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['كود خصم حصري'],
            'status' => CampaignStatus::ACTIVE,
            'badge' => 'الأكثر طلباً',
        ]);

        $response = $this->get(route('marketplace.show', $campaign->slug));
        $response->assertStatus(200);
        $response->assertSee('كوليكشن عبايات سارة الصيفية');
        $response->assertSee('Sara Couture');
        $response->assertSee('15%');
        $response->assertSee('450 ر.س');
        $response->assertSee('فيديو ريلز مدته 30 ثانية');
        $response->assertSee('هل ترغب بالترويج لهذه الحملة؟');
    }

    /**
     * Test brand can create a new campaign via Brand Portal.
     */
    public function test_brand_can_create_a_new_campaign_with_audit_log(): void
    {
        $payload = [
            'title' => 'حملة العطور الفاخرة للعام الجديد',
            'category' => 'عطور وتجميل',
            'description' => 'وصف مختصر للحملة والتخفيضات.',
            'brief' => 'شرح مطول للجمهور المستهدف ونوعية الزيوت العطرية.',
            'requirements' => 'فيديو تيك توك مراجعة عطرية.',
            'target_markets' => ['السعودية', 'الإمارات'],
            'currencies' => ['SAR', 'AED'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO->value,
            'commission_value' => '20%',
            'cookie_duration_days' => 45,
            'tracking_methods' => ['كود خصم حصري', 'رابط تتبع ذكي'],
            'avg_basket' => '350 ر.س',
            'conversion_rate' => '5.1%',
            'brand_guidelines' => 'يمنع ادعاء الثبات المضلل.',
            'badge' => 'عمولة حصرية',
        ];

        $response = $this->actingAs($this->brandUser)
            ->post(route('brand.campaigns.store'), $payload);

        $response->assertRedirect(route('brand.campaigns.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('campaigns', [
            'brand_profile_id' => $this->brandProfile->id,
            'title' => 'حملة العطور الفاخرة للعام الجديد',
            'commission_value' => '20%',
            'status' => CampaignStatus::ACTIVE->value,
        ]);

        $campaign = Campaign::where('title', 'حملة العطور الفاخرة للعام الجديد')->first();
        $this->assertNotNull($campaign);
        $this->assertNotEmpty($campaign->slug);

        // Verify Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->brandUser->id,
            'action' => AuditAction::CAMPAIGN_CREATED->value,
            'entity_type' => 'Campaign',
            'entity_id' => $campaign->id,
        ]);
    }

    /**
     * Test authorization: Brand cannot update or delete another brand's campaign.
     */
    public function test_brand_cannot_update_another_brands_campaign(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfile->id,
            'title' => 'حملة سارة الحصرية',
            'slug' => 'sara-exclusive',
            'category' => 'أزياء وموضة',
            'category_slug' => 'fashion',
            'description' => 'وصف الحملة',
            'brief' => 'موجز الحملة',
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15%',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['كود خصم حصري'],
            'status' => CampaignStatus::ACTIVE,
        ]);

        // Other brand attempts to update this campaign
        $response = $this->actingAs($this->otherBrandUser)
            ->put(route('brand.campaigns.update', $campaign), [
                'title' => 'تعديل مخترق',
                'category' => 'أزياء وموضة',
                'description' => 'وصف',
                'brief' => 'موجز',
                'target_markets' => ['السعودية'],
                'currencies' => ['SAR'],
                'commission_type' => CampaignCommissionType::PERCENTAGE_CPO->value,
                'commission_value' => '50%',
                'cookie_duration_days' => 30,
                'tracking_methods' => ['كود خصم حصري'],
                'status' => CampaignStatus::ACTIVE->value,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('campaigns', [
            'id' => $campaign->id,
            'title' => 'تعديل مخترق',
        ]);
    }

    /**
     * Test marketer can apply to an active campaign and submit pitch.
     */
    public function test_marketer_can_apply_to_active_campaign(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfile->id,
            'title' => 'حملة العبايات للمؤثرين',
            'slug' => 'influencers-abayas',
            'category' => 'أزياء وموضة',
            'category_slug' => 'fashion',
            'description' => 'وصف الحملة',
            'brief' => 'موجز الحملة',
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15%',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['كود خصم حصري'],
            'status' => CampaignStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->marketerUser)
            ->post(route('marketer.campaigns.apply', $campaign), [
                'pitch_note' => 'لدي أكثر من 50 ألف متابع مهتم بالأزياء والعبايات في الرياض وجدة.',
                'channels' => "https://instagram.com/myfashion\nhttps://tiktok.com/@myfashion",
            ]);

        $response->assertRedirect(route('marketplace.show', $campaign->slug));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('campaign_applications', [
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'status' => CampaignApplicationStatus::PENDING->value,
            'pitch_note' => 'لدي أكثر من 50 ألف متابع مهتم بالأزياء والعبايات في الرياض وجدة.',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->marketerUser->id,
            'action' => AuditAction::CAMPAIGN_APPLICATION_SUBMITTED->value,
        ]);
    }

    /**
     * Test brand can approve application and generate unique tracking code.
     */
    public function test_brand_can_approve_application_and_generate_tracking_code(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfile->id,
            'title' => 'حملة لافندر لصناع المحتوى',
            'slug' => 'lavender-creators',
            'category' => 'عطور وتجميل',
            'category_slug' => 'perfumes',
            'description' => 'وصف الحملة',
            'brief' => 'موجز الحملة',
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::FIXED_PER_ORDER,
            'commission_value' => '60 ر.س',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['كود خصم حصري'],
            'status' => CampaignStatus::ACTIVE,
        ]);

        $application = CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'status' => CampaignApplicationStatus::PENDING,
            'pitch_note' => 'أود الترويج للحملة.',
            'applied_at' => now(),
        ]);

        // Brand approves with custom code
        $response = $this->actingAs($this->brandUser)
            ->post(route('brand.campaigns.applications.review', [$campaign, $application]), [
                'action' => 'approve',
                'tracking_code' => 'SARA-KAMAL-15',
                'review_notes' => 'تم قبول طلبك بنجاح!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $application->refresh();
        $this->assertEquals(CampaignApplicationStatus::APPROVED, $application->status);
        $this->assertEquals('SARA-KAMAL-15', $application->tracking_code);
        $this->assertNotNull($application->tracking_link);
        $this->assertNotNull($application->reviewed_at);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->brandUser->id,
            'action' => AuditAction::CAMPAIGN_APPLICATION_APPROVED->value,
            'entity_id' => $application->id,
        ]);
    }

    /**
     * Test brand can reject application with review feedback note.
     */
    public function test_brand_can_reject_application_with_feedback(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfile->id,
            'title' => 'حملة تجريبية للرفض',
            'slug' => 'rejection-test',
            'category' => 'أزياء وموضة',
            'category_slug' => 'fashion',
            'description' => 'وصف',
            'brief' => 'موجز',
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '10%',
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

        $response = $this->actingAs($this->brandUser)
            ->post(route('brand.campaigns.applications.review', [$campaign, $application]), [
                'action' => 'reject',
                'review_notes' => 'نعتذر لعدم تطابق الجمهور المستهدف.',
            ]);

        $response->assertRedirect();
        $application->refresh();
        $this->assertEquals(CampaignApplicationStatus::REJECTED, $application->status);
        $this->assertEquals('نعتذر لعدم تطابق الجمهور المستهدف.', $application->review_notes);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->brandUser->id,
            'action' => AuditAction::CAMPAIGN_APPLICATION_REJECTED->value,
        ]);
    }

    /**
     * Test marketer can view their approved credentials in Marketer Workspace.
     */
    public function test_marketer_can_view_approved_tracking_code_in_workspace(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfile->id,
            'title' => 'حملة عبايات معتمدة',
            'slug' => 'approved-abayas',
            'category' => 'أزياء وموضة',
            'category_slug' => 'fashion',
            'description' => 'وصف',
            'brief' => 'موجز',
            'target_markets' => ['السعودية'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15%',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['كود خصم حصري'],
            'status' => CampaignStatus::ACTIVE,
        ]);

        CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $this->marketerProfile->id,
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'ACTIVE-CREATOR-99',
            'tracking_link' => 'https://example.com/?ref=active-creator-99',
            'applied_at' => now(),
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($this->marketerUser)
            ->get(route('marketer.campaigns.index'));

        $response->assertStatus(200);
        $response->assertSee('حملة عبايات معتمدة');
        $response->assertSee('ACTIVE-CREATOR-99');
        $response->assertSee('نسخ الكود');
    }

    /**
     * Test super admin can moderate and toggle campaign status platform-wide.
     */
    public function test_super_admin_can_moderate_any_campaign_status(): void
    {
        $campaign = Campaign::create([
            'brand_profile_id' => $this->brandProfile->id,
            'title' => 'حملة للإشراف الإداري',
            'slug' => 'admin-moderation-camp',
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

        // Super Admin pauses campaign
        $response = $this->actingAs($this->superAdmin)
            ->patch(route('admin.campaigns.status', $campaign), [
                'status' => CampaignStatus::PAUSED->value,
            ]);

        $response->assertRedirect();
        $campaign->refresh();
        $this->assertEquals(CampaignStatus::PAUSED, $campaign->status);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => AuditAction::CAMPAIGN_STATUS_CHANGED->value,
        ]);
    }
}
