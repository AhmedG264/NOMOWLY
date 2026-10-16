<?php

namespace Tests\Feature;

use App\Enums\MarketerSpecializationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BrandProfile;
use App\Models\MarketerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase2SecurityAndAuditTest extends TestCase
{
    use RefreshDatabase;

    // 1. Password reset request does not leak email existence
    public function test_password_reset_request_does_not_leak_email_existence(): void
    {
        // Non-existent email
        $response1 = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => 'doesnotexist@example.com',
        ]);

        $response1->assertRedirect(route('password.request'));
        $response1->assertSessionHas('status', 'إذا كان البريد الإلكتروني مسجلاً لدينا، فستتلقى رابطاً لإعادة تعيين كلمة المرور قريباً.');

        // Existing user email
        User::create([
            'name' => 'مستخدم موجود',
            'email' => 'existing@example.com',
            'password' => Hash::make('SecretPass123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
        ]);

        $response2 = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => 'existing@example.com',
        ]);

        $response2->assertRedirect(route('password.request'));
        $response2->assertSessionHas('status', 'إذا كان البريد الإلكتروني مسجلاً لدينا، فستتلقى رابطاً لإعادة تعيين كلمة المرور قريباً.');
    }

    // 2. Audit logs never store sensitive tokens or plaintext passwords
    public function test_audit_logs_never_store_plaintext_passwords_or_secrets(): void
    {
        $this->post(route('register.store'), [
            'role' => 'BRAND',
            'name' => 'شركة تدقيق',
            'company_name' => 'شركة التدقيق المالي',
            'email' => 'audit_brand@example.com',
            'password' => 'SuperSecretPlaintextPass123!',
            'password_confirmation' => 'SuperSecretPlaintextPass123!',
            'currency' => 'EGP',
        ]);

        $logs = \App\Models\AuditLog::all();
        $this->assertNotEmpty($logs);

        foreach ($logs as $log) {
            $json = json_encode($log->payload_diff);
            $this->assertStringNotContainsString('SuperSecretPlaintextPass123!', $json);
        }
    }

    // 3. Marketer primary specialization is strictly validated server-side
    public function test_marketer_registration_requires_valid_primary_specialization(): void
    {
        $response = $this->post(route('register.store'), [
            'role' => 'MARKETER',
            'name' => 'مسوق غير صالح',
            'email' => 'invalid_spec@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'primary_specialization' => 'INVALID_SPECIALIZATION',
        ]);

        $response->assertSessionHasErrors('primary_specialization');
        $this->assertDatabaseMissing('users', ['email' => 'invalid_spec@example.com']);
    }

    // 4. Session regeneration after login protects against session fixation
    public function test_session_id_regenerates_after_successful_login(): void
    {
        $user = User::create([
            'name' => 'فحص الجلسة',
            'email' => 'session_test@example.com',
            'password' => Hash::make('ValidPass123!'),
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);

        $this->get(route('login'));
        $initialSessionId = session()->getId();

        $this->post(route('login.store'), [
            'email' => 'session_test@example.com',
            'password' => 'ValidPass123!',
        ]);

        $newSessionId = session()->getId();
        $this->assertNotEquals($initialSessionId, $newSessionId);
    }
}
