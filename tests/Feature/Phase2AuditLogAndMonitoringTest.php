<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class Phase2AuditLogAndMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $brandUser;
    protected User $marketerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);

        $this->brandUser = User::factory()->create([
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        BrandProfile::create([
            'user_id' => $this->brandUser->id,
            'company_name' => 'Brand Test Co',
        ]);

        $this->marketerUser = User::factory()->create([
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
    }

    /**
     * 1. Super Admin can view audit logs index and show views.
     */
    public function test_super_admin_can_view_audit_logs(): void
    {
        // Seed some sample audit logs
        AuditService::log(
            action: AuditAction::CAMPAIGN_CREATED,
            userId: $this->brandUser->id,
            entityType: 'Campaign',
            entityId: 'camp-123',
            payloadDiff: ['title' => 'حملة العيد']
        );

        $log = AuditLog::first();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.audit-logs.index'));
        $response->assertStatus(200);
        $response->assertSee('سجل العمليات والمراقبة');
        $response->assertSee($this->brandUser->email);
        $response->assertSee('إنشاء حملة تسويقية جديدة');

        // Show page
        $showResponse = $this->actingAs($this->superAdmin)->get(route('admin.audit-logs.show', $log));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($log->id);
        $showResponse->assertSee('حملة العيد');
    }

    /**
     * 2. Unauthorized users cannot access audit logs.
     */
    public function test_unauthorized_users_cannot_access_audit_logs(): void
    {
        // Guest redirected to login
        $this->get(route('admin.audit-logs.index'))
            ->assertRedirect(route('login'));

        // Brand user forbidden (403)
        $brandResponse = $this->actingAs($this->brandUser)->get(route('admin.audit-logs.index'));
        $brandResponse->assertStatus(403);

        // Marketer user forbidden (403)
        $marketerResponse = $this->actingAs($this->marketerUser)->get(route('admin.audit-logs.index'));
        $marketerResponse->assertStatus(403);

        // Verify that unauthorized access attempt was recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::UNAUTHORIZED_ACCESS_ATTEMPT->value,
            'user_id' => $this->marketerUser->id,
            'status' => 'UNAUTHORIZED',
        ]);
    }

    /**
     * 3. Filtering and pagination work correctly.
     */
    public function test_audit_logs_filtering_and_pagination(): void
    {
        // Create 30 records to test pagination
        for ($i = 1; $i <= 30; $i++) {
            AuditService::log(
                action: $i % 2 === 0 ? AuditAction::LOGIN_SUCCESS : AuditAction::LOGIN_FAILED,
                userId: $i % 2 === 0 ? $this->superAdmin->id : null,
                status: $i % 2 === 0 ? 'SUCCESS' : 'FAILED',
                reason: $i % 2 === 0 ? null : 'خطأ في كلمة المرور'
            );
        }

        // Check first page (25 per page)
        $resPage1 = $this->actingAs($this->superAdmin)->get(route('admin.audit-logs.index'));
        $resPage1->assertStatus(200);
        $resPage1->assertSee('عرض 1 إلى 25 من أصل 30 سجل');

        // Check page 2
        $resPage2 = $this->actingAs($this->superAdmin)->get(route('admin.audit-logs.index', ['page' => 2]));
        $resPage2->assertStatus(200);
        $resPage2->assertSee('عرض 26 إلى 30 من أصل 30 سجل');

        // Filter by action: LOGIN_FAILED
        $filterAction = $this->actingAs($this->superAdmin)->get(route('admin.audit-logs.index', [
            'action' => AuditAction::LOGIN_FAILED->value,
        ]));
        $filterAction->assertStatus(200);
        $filterAction->assertSee('محاولة دخول فاشلة');
        $filterAction->assertSee('خطأ في كلمة المرور');

        // Filter by status: FAILED
        $filterStatus = $this->actingAs($this->superAdmin)->get(route('admin.audit-logs.index', [
            'status' => 'FAILED',
        ]));
        $filterStatus->assertStatus(200);
        $filterStatus->assertSee('فاشلة');
    }

    /**
     * 4. Real operation generates an audit log record.
     */
    public function test_real_operation_produces_audit_log(): void
    {
        // 1. Failed login attempt
        $this->post(route('login.store'), [
            'email' => 'wrong-user@nomowly.com',
            'password' => 'invalid-password',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::LOGIN_FAILED->value,
            'status' => 'FAILED',
            'reason' => 'بيانات الاعتماد المدخلة غير صحيحة',
        ]);

        // 2. Successful login
        $this->post(route('login.store'), [
            'email' => $this->superAdmin->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::LOGIN_SUCCESS->value,
            'user_id' => $this->superAdmin->id,
            'status' => 'SUCCESS',
        ]);
    }

    /**
     * 5. Audit log entries are strictly append-only and cannot be updated or deleted via application code.
     */
    public function test_audit_logs_cannot_be_updated_or_deleted(): void
    {
        $log = AuditService::log(
            action: AuditAction::CAMPAIGN_CREATED,
            userId: $this->superAdmin->id,
            status: 'SUCCESS'
        );

        // Attempting to update must throw RuntimeException
        try {
            $log->update(['status' => 'TAMPERED']);
            $this->fail('Expected RuntimeException on updating AuditLog, but none was thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('غير قابلة للتعديل', $e->getMessage());
        }

        // Attempting to delete must throw RuntimeException
        try {
            $log->delete();
            $this->fail('Expected RuntimeException on deleting AuditLog, but none was thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('غير قابلة للحذف', $e->getMessage());
        }

        // Verify database row is unaltered
        $freshLog = AuditLog::find($log->id);
        $this->assertNotNull($freshLog);
        $this->assertEquals('SUCCESS', $freshLog->status);
    }

    /**
     * 6. Audit logs never store plaintext passwords, tokens, or sensitive secrets.
     */
    public function test_audit_logs_never_store_sensitive_secrets(): void
    {
        AuditService::log(
            action: AuditAction::USER_PROFILE_UPDATED,
            userId: $this->superAdmin->id,
            payloadDiff: [
                'username' => 'ahmed',
                'password' => 'super-secret-password-123',
                'api_secret' => 'sk_live_secret_key_456',
                'credit_card' => '4111222233334444',
                'nested' => [
                    'bearer_token' => 'jwt.token.string',
                    'normal_field' => 'visible',
                ],
            ]
        );

        $log = AuditLog::latest('created_at')->first();
        $this->assertNotNull($log);

        // Verify redaction
        $this->assertEquals('ahmed', $log->payload_diff['username']);
        $this->assertEquals('[REDACTED]', $log->payload_diff['password']);
        $this->assertEquals('[REDACTED]', $log->payload_diff['api_secret']);
        $this->assertEquals('[REDACTED]', $log->payload_diff['credit_card']);
        $this->assertEquals('[REDACTED]', $log->payload_diff['nested']['bearer_token']);
        $this->assertEquals('visible', $log->payload_diff['nested']['normal_field']);
    }
}
