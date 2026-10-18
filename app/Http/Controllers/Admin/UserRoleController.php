<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserRoleController extends Controller
{
    /**
     * Display a listing of platform users with their assigned roles and profiles.
     */
    public function index(Request $request): View
    {
        $query = User::with(['roles', 'brandProfile', 'marketerProfile'])->latest('created_at');

        // Search by email, name, or ID
        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // Filter by Role
        if ($request->filled('role')) {
            $roleFilter = $request->input('role');
            $query->where(function ($q) use ($roleFilter) {
                $q->where('role', $roleFilter)
                  ->orWhereHas('roles', function ($rq) use ($roleFilter) {
                      $rq->where('name', $roleFilter);
                  });
            });
        }

        $users = $query->paginate(20)->withQueryString();
        $allRoles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'allRoles'));
    }

    /**
     * Show the role assignment interface for a specific user.
     */
    public function edit(User $user): View
    {
        $user->load('roles');
        $availableRoles = Role::orderByDesc('is_system')->orderBy('name')->get();

        return view('admin.users.roles', compact('user', 'availableRoles'));
    }

    /**
     * Update the assigned roles for a user with strict security enforcement.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['uuid', 'exists:roles,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [
            'roles.required' => 'يجب تحديد دور واحد على الأقل للمستخدم.',
            'roles.min' => 'يجب تحديد دور واحد على الأقل للمستخدم.',
        ]);

        $selectedRoles = Role::whereIn('id', $validated['roles'])->get();
        $isAssigningSuperAdmin = $selectedRoles->contains('name', UserRole::SUPER_ADMIN->value);
        $currentUser = $request->user();

        // 1. Security Check: Only an active Super Admin can assign or grant SUPER_ADMIN
        if ($isAssigningSuperAdmin && !$currentUser->isSuperAdmin()) {
            AuditService::log(
                action: AuditAction::UNAUTHORIZED_ACCESS_ATTEMPT,
                userId: $currentUser->id,
                entityType: 'UserRoleAssignment',
                entityId: $user->id,
                payloadDiff: ['attempted_role' => UserRole::SUPER_ADMIN->value],
                status: 'UNAUTHORIZED',
                reason: 'محاولة غير مصرح بها لمنح رتبة المدير العام (Super Admin)'
            );

            abort(403, 'غير مصرح لك بمنح رتبة المدير العام.');
        }

        // 2. Prevent removing the last Super Admin
        if ($user->isSuperAdmin() && !$isAssigningSuperAdmin) {
            $superAdminsCount = User::where('role', UserRole::SUPER_ADMIN)
                ->orWhereHas('roles', function ($q) {
                    $q->where('name', UserRole::SUPER_ADMIN->value);
                })->count();

            if ($superAdminsCount <= 1) {
                return back()->with('error', 'لا يمكن سحب صلاحية المدير العام من هذا الحساب؛ يجب أن يظل مدير عام واحد على الأقل في النظام.');
            }
        }

        // 3. Prevent self-demotion lockout if user is managing their own account
        if ($user->id === $currentUser->id && $currentUser->isSuperAdmin() && !$isAssigningSuperAdmin) {
            return back()->with('error', 'لا يمكنك سحب صلاحية المدير العام من حسابك الشخصي منعاً لفقدان الوصول.');
        }

        $oldRoles = $user->roles->pluck('name')->toArray();
        if (empty($oldRoles) && $user->role) {
            $oldRoles = [$user->role->value];
        }

        $newRoles = $selectedRoles->pluck('name')->toArray();

        // Perform synchronization
        $user->syncRoles($validated['roles'], $currentUser);

        // Audit Trail
        AuditService::log(
            action: AuditAction::USER_ROLES_ASSIGNED,
            userId: $currentUser->id,
            entityType: 'User',
            entityId: $user->id,
            payloadDiff: [
                'target_user_id' => $user->id,
                'target_email' => $user->email,
                'old_roles' => $oldRoles,
                'new_roles' => $newRoles,
            ],
            status: 'SUCCESS',
            reason: $validated['reason'] ?? 'تعديل وإسناد أدوار المستخدم من لوحة الإدارة'
        );

        return redirect()->route('admin.users.index')
            ->with('status', "تم تحديث أدوار المستخدم ({$user->email}) بنجاح.");
    }
}
