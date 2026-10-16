<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\MarketerSpecializationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\BrandProfile;
use App\Models\MarketerProfile;
use App\Models\MarketerSpecialization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class Phase2AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // 1. User Registration (Marketer)
    public function test_marketer_registration_creates_user_profile_and_specializations(): void
    {
        $response = $this->post(route('register.store'), [
            'role' => 'MARKETER',
            'name' => 'أحمد المسوق',
            'email' => 'marketer@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'primary_specialization' => 'CREATOR',
            'specializations' => ['CREATOR', 'AFFILIATE'],
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('marketer.dashboard'));

        $user = User::where('email', 'marketer@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(UserRole::MARKETER, $user->role);
        $this->assertFalse($user->must_change_password);

        // Verify profile and specializations
        $profile = MarketerProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals('أحمد المسوق', $profile->full_name);

        $specializations = MarketerSpecialization::where('marketer_profile_id', $profile->id)->get();
        $this->assertCount(2, $specializations);

        $primary = $specializations->firstWhere('is_primary', true);
        $this->assertNotNull($primary);
        $this->assertEquals(MarketerSpecializationType::CREATOR, $primary->specialization);

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => AuditAction::REGISTER_MARKETER->value,
        ]);
    }

    // 2. Brand Registration
    public function test_brand_registration_creates_user_and_brand_profile(): void
    {
        $response = $this->post(route('register.store'), [
            'role' => 'BRAND',
            'name' => 'سارة المديرة',
            'company_name' => 'متجر الأناقة',
            'email' => 'brand@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'website_url' => 'https://elegance.com',
            'currency' => 'EGP',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('brand.dashboard'));

        $user = User::where('email', 'brand@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(UserRole::BRAND, $user->role);

        $brandProfile = BrandProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($brandProfile);
        $this->assertEquals('متجر الأناقة', $brandProfile->company_name);
        $this->assertEquals('EGP', $brandProfile->currency);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => AuditAction::REGISTER_BRAND->value,
        ]);
    }

    // 3. Public registration rejects SUPER_ADMIN (Security)
    public function test_public_registration_rejects_super_admin_role_attempt(): void
    {
        $response = $this->post(route('register.store'), [
            'role' => 'SUPER_ADMIN',
            'name' => 'مخترق محتمل',
            'email' => 'hacker@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);
    }

    // 4. Login with valid credentials and session regeneration
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::create([
            'name' => 'مسوق معتمد',
            'email' => 'user@example.com',
            'password' => Hash::make('SecretPass123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'user@example.com',
            'password' => 'SecretPass123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('marketer.dashboard'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => AuditAction::LOGIN_SUCCESS->value,
        ]);
    }

    // 5. Login with invalid credentials fails and logs audit event
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::create([
            'name' => 'مسوق معتمد',
            'email' => 'user@example.com',
            'password' => Hash::make('SecretPass123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'user@example.com',
            'password' => 'WrongPassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::LOGIN_FAILED->value,
        ]);
    }

    // 6. Logout terminates session
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::create([
            'name' => 'مسوق',
            'email' => 'logout@example.com',
            'password' => Hash::make('SecretPass123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect('/');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => AuditAction::LOGOUT->value,
        ]);
    }

    // 7. Password hashing verification (never plaintext)
    public function test_passwords_are_never_stored_in_plaintext(): void
    {
        $plain = 'ComplexSecret123!';
        $user = User::create([
            'name' => 'مستخدم آمن',
            'email' => 'hashed@example.com',
            'password' => Hash::make($plain),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->assertNotEquals($plain, $user->password);
        $this->assertTrue(Hash::check($plain, $user->password));
    }

    // 8. must_change_password enforcement redirects to password change
    public function test_must_change_password_flag_forces_redirection(): void
    {
        $user = User::create([
            'name' => 'مشرف جديد',
            'email' => 'admin@example.com',
            'password' => Hash::make('TempPassword123!'),
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => true,
        ]);

        // Attempting to visit protected admin dashboard
        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertRedirect(route('password.change'));
    }

    // 9. Password change updates must_change_password flag
    public function test_user_can_change_mandatory_password(): void
    {
        $user = User::create([
            'name' => 'مشرف',
            'email' => 'change@example.com',
            'password' => Hash::make('InitialTemp123!'),
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->post(route('password.change.update'), [
            'current_password' => 'InitialTemp123!',
            'password' => 'NewStrongSecret2026@!',
            'password_confirmation' => 'NewStrongSecret2026@!',
        ]);

        $response->assertRedirect(route('admin.dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('NewStrongSecret2026@!', $user->password));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => AuditAction::PASSWORD_CHANGE->value,
        ]);
    }

    // 10. SUPER_ADMIN authorization allows admin route access
    public function test_super_admin_can_access_admin_routes_after_password_change(): void
    {
        $admin = User::create([
            'name' => 'المدير العام',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('SecretPass123!'),
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('لوحة التحكم الإدارية العليا');
    }

    // 11. BRAND cannot access admin routes
    public function test_brand_cannot_access_admin_routes(): void
    {
        $brand = User::create([
            'name' => 'علامة تجارية',
            'email' => 'brand_denied@example.com',
            'password' => Hash::make('SecretPass123!'),
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($brand)->get(route('admin.dashboard'));

        $response->assertStatus(403);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $brand->id,
            'action' => AuditAction::UNAUTHORIZED_ACCESS_ATTEMPT->value,
        ]);
    }

    // 12. MARKETER cannot access admin routes
    public function test_marketer_cannot_access_admin_routes(): void
    {
        $marketer = User::create([
            'name' => 'مسوق',
            'email' => 'marketer_denied@example.com',
            'password' => Hash::make('SecretPass123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($marketer)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    // 13. Super Admin Bootstrap Command creates correct admin idempotently
    public function test_bootstrap_admin_command_creates_super_admin_idempotently(): void
    {
        putenv('ADMIN_EMAIL=ahmedgamal264@outlook.com');
        putenv('ADMIN_INITIAL_PASSWORD=StrongAdminSecret2026!#');

        // First run: should create
        $this->artisan('nomowly:bootstrap-admin')
            ->expectsOutputToContain('SUCCESS: Initial Super Admin account created')
            ->assertExitCode(0);

        $admin = User::where('email', 'ahmedgamal264@outlook.com')->first();
        $this->assertNotNull($admin);
        $this->assertEquals(UserRole::SUPER_ADMIN, $admin->role);
        $this->assertTrue($admin->must_change_password);
        $this->assertTrue(Hash::check('StrongAdminSecret2026!#', $admin->password));

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditAction::SUPER_ADMIN_BOOTSTRAP->value,
        ]);

        // Second run: should be idempotent and not overwrite
        $this->artisan('nomowly:bootstrap-admin')
            ->expectsOutputToContain('IDEMPOTENT STATUS: Super Admin account for [ahmedgamal264@outlook.com] already exists.')
            ->assertExitCode(0);
    }

    // 14. Existing non-admin user cannot be silently promoted
    public function test_bootstrap_command_fails_safely_if_email_is_regular_user(): void
    {
        User::create([
            'name' => 'مستخدم عادي',
            'email' => 'ahmedgamal264@outlook.com',
            'password' => Hash::make('SomeOldPass123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
        ]);

        putenv('ADMIN_EMAIL=ahmedgamal264@outlook.com');
        putenv('ADMIN_INITIAL_PASSWORD=StrongAdminSecret2026!#');

        $this->artisan('nomowly:bootstrap-admin')
            ->expectsOutputToContain('CRITICAL SAFETY HALT')
            ->assertExitCode(1);

        $user = User::where('email', 'ahmedgamal264@outlook.com')->first();
        $this->assertEquals(UserRole::MARKETER, $user->role); // Did NOT get silently promoted!
    }

    // 15. Login Rate Limiting protects against brute-force attacks
    public function test_login_throttling_triggers_after_repeated_failures(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), [
                'email' => 'bruteforce@example.com',
                'password' => 'WrongPassword',
            ]);
        }

        // 6th attempt should be blocked by rate limiter
        $response = $this->post(route('login.store'), [
            'email' => 'bruteforce@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertSessionHasErrors('email');
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('لقد تجاوزت عدد محاولات الدخول', $errors[0]);
    }
}
