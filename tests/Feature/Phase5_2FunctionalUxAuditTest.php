<?php

namespace Tests\Feature;

use App\Enums\CampaignApplicationStatus;
use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\MarketerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase5_2FunctionalUxAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_routes_have_no_dead_links_or_window_alerts(): void
    {
        $routes = ['/', '/marketplace', '/for-brands', '/for-marketers', '/how-it-works', '/pricing', '/login', '/register'];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);

            // Assert no dead links or alert calls
            $content = $response->getContent();
            $this->assertStringNotContainsString('href="#"', $content, "Route {$route} contains href='#'");
            $this->assertStringNotContainsString('href=""', $content, "Route {$route} contains empty href");
            $this->assertStringNotContainsString('javascript:void(0)', $content, "Route {$route} contains javascript:void(0)");
            $this->assertStringNotContainsString('alert(', $content, "Route {$route} contains window.alert()");
        }
    }

    public function test_brand_dashboard_hub_has_real_functional_action_links(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        BrandProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Sara Couture',
            'currency' => 'SAR',
        ]);

        $response = $this->actingAs($user)->get(route('brand.dashboard'));
        $response->assertStatus(200);

        $response->assertSee(route('brand.campaigns.index'));
        $response->assertSee(route('brand.campaigns.create'));
        $response->assertSee(route('brand.conversions.index'));
        $response->assertSee(route('marketplace'));
        $response->assertDontSee('href="#"', false);
        $response->assertDontSee('alert(', false);
    }

    public function test_marketer_dashboard_hub_has_real_functional_action_links(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        MarketerProfile::create([
            'user_id' => $user->id,
            'full_name' => 'Ahmed Marketer',
            'phone' => '+966500000000',
        ]);

        $response = $this->actingAs($user)->get(route('marketer.dashboard'));
        $response->assertStatus(200);

        $response->assertSee(route('marketer.campaigns.index'));
        $response->assertSee(route('marketplace'));
        $response->assertSee(route('for-marketers'));
        $response->assertDontSee('href="#"', false);
        $response->assertDontSee('alert(', false);
    }

    public function test_admin_dashboard_hub_has_real_functional_action_links(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $response->assertSee(route('admin.campaigns.index'));
        $response->assertSee(route('admin.campaigns.index'));
        $response->assertDontSee('href="#"', false);
        $response->assertDontSee('alert(', false);
    }

    public function test_marketer_workspace_clipboard_actions_render_cleanly_without_alert(): void
    {
        $marketerUser = User::factory()->create([
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $marketerProfile = MarketerProfile::create([
            'user_id' => $marketerUser->id,
            'full_name' => 'Fahad Creator',
            'phone' => '+966511111111',
        ]);

        $brandUser = User::factory()->create([
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $brandProfile = BrandProfile::create([
            'user_id' => $brandUser->id,
            'company_name' => 'Brand Perfumes',
            'currency' => 'SAR',
        ]);

        $campaign = Campaign::create([
            'brand_profile_id' => $brandProfile->id,
            'title' => 'Perfume Launch',
            'slug' => 'perfume-launch-' . Str::random(5),
            'category' => 'عطور',
            'category_slug' => 'perfumes',
            'description' => 'Test campaign description',
            'brief' => 'Campaign details brief',
            'target_markets' => ['SA'],
            'currencies' => ['SAR'],
            'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
            'commission_value' => '15%',
            'cookie_duration_days' => 30,
            'tracking_methods' => ['COUPON', 'LINK'],
            'status' => CampaignStatus::ACTIVE,
            'destination_url' => 'https://example.com/store',
            'starts_at' => now(),
        ]);

        CampaignApplication::create([
            'campaign_id' => $campaign->id,
            'marketer_profile_id' => $marketerProfile->id,
            'status' => CampaignApplicationStatus::APPROVED,
            'tracking_code' => 'NMW-TEST-99',
            'tracking_link' => config('app.url') . '/go/NMW-TEST-99',
            'applied_at' => now(),
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($marketerUser)->get(route('marketer.campaigns.index'));
        $response->assertStatus(200);
        $response->assertSee('NMW-TEST-99');
        $response->assertSee('تم النسخ ✓');
        $response->assertDontSee('alert(', false);

        $detailResponse = $this->actingAs($marketerUser)->get(route('marketplace.show', $campaign->slug));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('NMW-TEST-99');
        $detailResponse->assertSee('تم النسخ ✓');
        $detailResponse->assertDontSee('alert(', false);
    }
}
