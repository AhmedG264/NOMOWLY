<?php

namespace Tests\Feature;

use App\Enums\MarketerSpecializationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BrandProfile;
use App\Models\MarketerProfile;
use App\Models\MarketerSpecialization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class Phase2AssetAndUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_exists_and_contains_valid_built_assets(): void
    {
        $manifestPath = public_path('build/manifest.json');
        $this->assertTrue(File::exists($manifestPath), 'public/build/manifest.json must exist');

        $manifest = json_decode(File::get($manifestPath), true);
        $this->assertArrayHasKey('resources/css/app.css', $manifest);
        $this->assertArrayHasKey('resources/js/app.js', $manifest);

        $cssFile = public_path('build/' . $manifest['resources/css/app.css']['file']);
        $jsFile = public_path('build/' . $manifest['resources/js/app.js']['file']);

        $this->assertTrue(File::exists($cssFile), 'Compiled CSS asset file must exist');
        $this->assertTrue(File::exists($jsFile), 'Compiled JS asset file must exist');

        $this->assertFalse(File::exists(public_path('hot')), 'Vite hot file must not exist in production/preview build');

        $cssContent = File::get($cssFile);
        $this->assertStringContainsString('Tajawal', $cssContent, 'Compiled CSS must reference Tajawal font');
        $this->assertStringContainsString('direction:rtl', $cssContent, 'Compiled CSS must include RTL rules');
    }

    public function test_login_page_renders_with_rtl_and_assets(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('Tajawal', false);
        $response->assertSee('/build/assets/app-', false);
        $response->assertSee('تسجيل الدخول إلى نمولي');
    }

    public function test_register_page_renders_with_rtl_and_assets(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('Tajawal', false);
        $response->assertSee('/build/assets/app-', false);
        $response->assertSee('إنشاء حساب جديد في منصة نمولي');
        $response->assertSee('مسوق بالعمولة (Marketer)');
        $response->assertSee('علامة تجارية (Brand)');
    }

    public function test_forgot_password_page_renders_with_rtl_and_assets(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('Tajawal', false);
        $response->assertSee('/build/assets/app-', false);
        $response->assertSee('استعادة كلمة المرور');
    }

    public function test_reset_password_page_renders_with_rtl_and_assets(): void
    {
        $response = $this->get('/reset-password/sample-token?email=test@nomowly.com');

        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('Tajawal', false);
        $response->assertSee('/build/assets/app-', false);
        $response->assertSee('إعادة تعيين كلمة المرور');
    }

    public function test_password_change_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->get('/password/change');

        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('تغيير كلمة المرور الإلزامي');
        $response->assertSee('/build/assets/app-', false);
    }

    public function test_verify_email_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('تأكيد البريد الإلكتروني');
        $response->assertSee('/build/assets/app-', false);
    }

    public function test_admin_dashboard_renders_for_super_admin(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertRedirect(route('admin.dashboard'));

        $dashResponse = $this->actingAs($admin)->get('/admin/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('dir="rtl"', false);
        $dashResponse->assertSee('لوحة التحكم الإدارية العليا');
        $dashResponse->assertSee('/build/assets/app-', false);
    }

    public function test_brand_dashboard_renders_for_brand_user(): void
    {
        $brand = User::factory()->create([
            'role' => UserRole::BRAND,
            'must_change_password' => false,
        ]);

        BrandProfile::create([
            'user_id' => $brand->id,
            'company_name' => 'شركة النخبة',
            'currency' => 'SAR',
        ]);

        $response = $this->actingAs($brand)->get('/brand');
        $response->assertRedirect(route('brand.dashboard'));

        $dashResponse = $this->actingAs($brand)->get('/brand/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('dir="rtl"', false);
        $dashResponse->assertSee('لوحة تحكم العلامة التجارية');
        $dashResponse->assertSee('/build/assets/app-', false);
    }

    public function test_marketer_dashboard_renders_for_marketer_user(): void
    {
        $marketer = User::factory()->create([
            'role' => UserRole::MARKETER,
            'must_change_password' => false,
        ]);

        $profile = MarketerProfile::create([
            'user_id' => $marketer->id,
            'full_name' => 'محمد المسوق',
        ]);

        MarketerSpecialization::create([
            'marketer_profile_id' => $profile->id,
            'specialization' => MarketerSpecializationType::CREATOR,
            'is_primary' => true,
        ]);

        $response = $this->actingAs($marketer)->get('/marketer');
        $response->assertRedirect(route('marketer.dashboard'));

        $dashResponse = $this->actingAs($marketer)->get('/marketer/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('dir="rtl"', false);
        $dashResponse->assertSee('لوحة تحكم المسوق');
        $dashResponse->assertSee('/build/assets/app-', false);
    }
}
