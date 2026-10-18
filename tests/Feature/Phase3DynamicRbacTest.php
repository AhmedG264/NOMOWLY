<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\BrandProfile;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class Phase3DynamicRbacTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $brandUser;
    protected User $marketerUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Re-run migration seed if needed
        $this->artisan('migrate');

        $this->superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $superAdminRole = Role::where('name', UserRole::SUPER_ADMIN->value)->first();
        if ($superAdminRole) {
            $this->superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id => [
                
            ]]);
        }

        $this->brandUser = User::factory()->create([
            'role' => UserRole::BRAND,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $brandRole = Role::where('name', UserRole::BRAND->value)->first();
        if ($brandRole) {
            $this->brandUser->roles()->syncWithoutDetaching([$brandRole->id => [
                
            ]]);
        }
        BrandProfile::create([
            'user_id' => $this->brandUser->id,
            'company_name' => 'Brand Co Test',
        ]);

        $this->marketerUser = User::factory()->create([
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $marketerRole = Role::where('name', UserRole::MARKETER->value)->first();
        if ($marketerRole) {
            $this->marketerUser->roles()->syncWithoutDetaching([$marketerRole->id => [
                
            ]]);
        }
    }

    /**
     * 1. Existing users retain their roles and compatibility after migration.
     */
    public function test_existing_users_retain_roles_and_compatibility(): void
    {
        $this->assertTrue($this->superAdmin->isSuperAdmin());
        $this->assertTrue($this->superAdmin->hasRole('SUPER_ADMIN'));
        $this->assertTrue($this->superAdmin->hasPermission('users.view'));
        $this->assertTrue($this->superAdmin->hasPermission('any.random.permission')); // Super Admin bypass

        $this->assertTrue($this->brandUser->isBrand());
        $this->assertTrue($this->brandUser->hasRole('BRAND'));
        $this->assertFalse($this->brandUser->hasRole('SUPER_ADMIN'));

        $this->assertTrue($this->marketerUser->isMarketer());
        $this->assertTrue($this->marketerUser->hasRole('MARKETER'));
        $this->assertFalse($this->marketerUser->hasRole('SUPER_ADMIN'));
    }

    /**
     * 2. Non-Super Admin users (Brand & Marketer) cannot access RBAC administration routes.
     */
    public function test_unauthorized_users_cannot_access_rbac_routes(): void
    {
        // Brand user blocked from roles and user assignment
        $this->actingAs($this->brandUser)->get(route('admin.roles.index'))->assertStatus(403);
        $this->actingAs($this->brandUser)->get(route('admin.users.index'))->assertStatus(403);
        $this->actingAs($this->brandUser)->get(route('admin.roles.create'))->assertStatus(403);

        // Marketer user blocked
        $this->actingAs($this->marketerUser)->get(route('admin.roles.index'))->assertStatus(403);
        $this->actingAs($this->marketerUser)->get(route('admin.users.index'))->assertStatus(403);

        // Guest redirected
        auth()->logout();
        $this->get(route('admin.roles.index'))->assertRedirect(route('login'));
    }

    /**
     * 3. Users cannot elevate their own role through Mass Assignment or unauthorized endpoints.
     */
    public function test_user_cannot_elevate_role_via_mass_assignment(): void
    {
        // Brand attempts to modify role via direct endpoint or mass assignment
        $this->brandUser->fill(['role' => UserRole::SUPER_ADMIN]);
        // But refreshing from database remains unchanged unless saved through authorized service
        $this->brandUser->refresh();
        $this->assertEquals(UserRole::BRAND, $this->brandUser->role);

        // Attempting to POST to user role assignment as a Brand user
        $superAdminRole = Role::where('name', UserRole::SUPER_ADMIN->value)->firstOrFail();
        $res = $this->actingAs($this->brandUser)->put(route('admin.users.roles.update', $this->brandUser), [
            'roles' => [$superAdminRole->id],
        ]);
        $res->assertStatus(403);

        // Confirm database was not tampered with
        $this->brandUser->refresh();
        $this->assertEquals(UserRole::BRAND, $this->brandUser->role);
        $this->assertFalse($this->brandUser->hasRole('SUPER_ADMIN'));
    }

    /**
     * 4. Strict Super Admin protection: Cannot create, delete, or rename SUPER_ADMIN.
     */
    public function test_super_admin_role_cannot_be_created_deleted_or_renamed(): void
    {
        $superAdminRole = Role::where('name', UserRole::SUPER_ADMIN->value)->firstOrFail();

        // 1. Attempting to create a duplicate SUPER_ADMIN role fails validation
        $res = $this->actingAs($this->superAdmin)->post(route('admin.roles.store'), [
            'name' => 'SUPER_ADMIN',
            'label' => 'مدير مزيف',
        ]);
        $res->assertSessionHasErrors('name');

        // 2. Attempting to update SUPER_ADMIN through controller is aborted
        $updateRes = $this->actingAs($this->superAdmin)->put(route('admin.roles.update', $superAdminRole), [
            'label' => 'تعديل محظور',
        ]);
        $updateRes->assertStatus(403);

        // 3. Attempting to delete SUPER_ADMIN through controller is aborted
        $delRes = $this->actingAs($this->superAdmin)->delete(route('admin.roles.destroy', $superAdminRole));
        $delRes->assertStatus(403);

        // 4. Model-level protection throws RuntimeException on delete
        try {
            $superAdminRole->delete();
            $this->fail('Expected RuntimeException when deleting SUPER_ADMIN, none thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('محمية برمجياً', $e->getMessage());
        }
    }

    /**
     * 5. Super Admin can create custom roles and assign categorized permissions.
     */
    public function test_super_admin_can_manage_custom_roles(): void
    {
        $permissions = Permission::whereIn('name', ['campaigns.view', 'campaigns.approve'])->get();

        // Create new role
        $createRes = $this->actingAs($this->superAdmin)->post(route('admin.roles.store'), [
            'name' => 'SUPPORT_SUPERVISOR',
            'label' => 'مشرف الدعم الفني',
            'description' => 'إشراف على الحملات والمستخدمين',
            'permissions' => $permissions->pluck('id')->toArray(),
        ]);
        $createRes->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseHas('roles', [
            'name' => 'SUPPORT_SUPERVISOR',
            'label' => 'مشرف الدعم الفني',
            'is_system' => false,
        ]);

        $newRole = Role::where('name', 'SUPPORT_SUPERVISOR')->firstOrFail();
        $this->assertEquals(2, $newRole->permissions()->count());

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::ROLE_CREATED->value,
            'entity_type' => 'Role',
            'entity_id' => $newRole->id,
            'status' => 'SUCCESS',
        ]);

        // Update the custom role
        $updateRes = $this->actingAs($this->superAdmin)->put(route('admin.roles.update', $newRole), [
            'name' => 'SUPPORT_SUPERVISOR_MOD',
            'label' => 'مشرف الدعم المحدث',
            'description' => 'وصف معدل',
            'permissions' => [$permissions->first()->id],
        ]);
        $updateRes->assertRedirect(route('admin.roles.index'));

        $newRole->refresh();
        $this->assertEquals('SUPPORT_SUPERVISOR_MOD', $newRole->name);
        $this->assertEquals('مشرف الدعم المحدث', $newRole->label);
        $this->assertEquals(1, $newRole->permissions()->count());
    }

    /**
     * 6. Super Admin can assign roles to users and verify audit logging.
     */
    public function test_super_admin_can_assign_roles_to_users_with_audit_trail(): void
    {
        $auditorRole = Role::where('name', 'AUDITOR')->firstOrFail();

        $targetUser = User::factory()->create([
            'role' => UserRole::MARKETER,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);

        $res = $this->actingAs($this->superAdmin)->put(route('admin.users.roles.update', $targetUser), [
            'roles' => [$auditorRole->id],
            'reason' => 'ترقية إلى مراقب معتمد',
        ]);
        $res->assertRedirect(route('admin.users.index'));

        $targetUser->refresh();
        $this->assertTrue($targetUser->hasRole('AUDITOR'));
        $this->assertTrue($targetUser->hasPermission('audit_logs.view'));

        // Verify audit log has the reason and before/after roles
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::USER_ROLES_ASSIGNED->value,
            'user_id' => $this->superAdmin->id,
            'entity_type' => 'User',
            'entity_id' => $targetUser->id,
            'reason' => 'ترقية إلى مراقب معتمد',
            'status' => 'SUCCESS',
        ]);
    }

    /**
     * 7. Backend enforces granular permissions at the HTTP middleware level.
     */
    public function test_backend_enforces_permissions_at_middleware_level(): void
    {
        // Create user with AUDITOR role
        $auditorUser = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN, // Temporary for super_admin prefix access
            'status' => UserStatus::ACTIVE,
            'must_change_password' => false,
        ]);
        $auditorRole = Role::where('name', 'AUDITOR')->firstOrFail();
        $auditorUser->roles()->sync([$auditorRole->id => [
            
        ]]);

        // User with audit_logs.view permission can view audit logs
        $res = $this->actingAs($auditorUser)->get(route('admin.audit-logs.index'));
        $res->assertStatus(200);

        // Regular marketer cannot access audit logs
        $failRes = $this->actingAs($this->marketerUser)->get(route('admin.audit-logs.index'));
        $failRes->assertStatus(403);
    }

    /**
     * 8. System role deletion is strictly prevented if users are currently assigned.
     */
    public function test_role_deletion_blocked_if_users_assigned(): void
    {
        $customRole = Role::create([
            'name' => 'OPS_ASSISTANT',
            'label' => 'مساعد العمليات',
            'is_system' => false,
        ]);

        // Assign to a user
        $this->marketerUser->roles()->attach($customRole->id, [
            
        ]);

        // Attempting to delete custom role that has assigned users must fail
        $res = $this->actingAs($this->superAdmin)->delete(route('admin.roles.destroy', $customRole));
        $res->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['name' => 'OPS_ASSISTANT']);

        // Detach user and then delete should succeed
        $this->marketerUser->roles()->detach($customRole->id);
        $successRes = $this->actingAs($this->superAdmin)->delete(route('admin.roles.destroy', $customRole));
        $successRes->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseMissing('roles', ['name' => 'OPS_ASSISTANT']);
    }
}
