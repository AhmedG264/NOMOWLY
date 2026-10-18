<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * Display a listing of all roles with counts of users and assigned permissions.
     */
    public function index(): View
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        $stats = [
            'total_roles' => Role::count(),
            'system_roles' => Role::where('is_system', true)->count(),
            'custom_roles' => Role::where('is_system', false)->count(),
            'total_permissions' => Permission::count(),
        ];

        return view('admin.roles.index', compact('roles', 'stats'));
    }

    /**
     * Show the form for creating a new custom administrative role.
     */
    public function create(): View
    {
        $permissionsByCategory = Permission::orderBy('category')->orderBy('name')->get()->groupBy('category');

        return view('admin.roles.create', compact('permissionsByCategory'));
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Z0-9_]+$/',
                Rule::unique('roles', 'name'),
                Rule::notIn([UserRole::SUPER_ADMIN->value, UserRole::BRAND->value, UserRole::MARKETER->value]),
            ],
            'label' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['uuid', 'exists:permissions,id'],
        ], [
            'name.regex' => 'يجب أن يتكون الاسم الرمزي للدور من حروف إنجليزية كبيرة وأرقام وشرطة سفلية فقط (مثال: OPERATIONS_OFFICER).',
            'name.unique' => 'الاسم الرمزي للدور مستخدم مسبقاً، يرجى اختيار اسم آخر.',
            'name.not_in' => 'لا يمكن استخدام أسماء الأدوار الأساسية المحمية للنظام.',
        ]);

        $role = Role::create([
            'name' => Str::upper(trim($validated['name'])),
            'label' => trim($validated['label']),
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        AuditService::log(
            action: AuditAction::ROLE_CREATED,
            userId: $request->user()->id,
            entityType: 'Role',
            entityId: $role->id,
            payloadDiff: [
                'name' => $role->name,
                'label' => $role->label,
                'permissions_count' => count($validated['permissions'] ?? []),
            ],
            status: 'SUCCESS',
            reason: 'إنشاء دور إداري جديد وتعيين صلاحياته'
        );

        return redirect()->route('admin.roles.index')
            ->with('status', "تم إنشاء الدور الإداري ({$role->label}) بنجاح.");
    }

    /**
     * Show the form for editing an existing role.
     */
    public function edit(Role $role): View
    {
        $role->load('permissions');
        $permissionsByCategory = Permission::orderBy('category')->orderBy('name')->get()->groupBy('category');

        return view('admin.roles.edit', compact('role', 'permissionsByCategory'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        // Enforce strict protection for Super Admin
        if ($role->name === UserRole::SUPER_ADMIN->value) {
            abort(403, 'دور المدير العام (Super Admin) محمي برمجياً ولا يمكن تعديل صلاحياته أو مسمياته من الواجهة.');
        }

        $rules = [
            'label' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['uuid', 'exists:permissions,id'],
        ];

        // Non-system roles can have their name edited
        if (!$role->is_system) {
            $rules['name'] = [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Z0-9_]+$/',
                Rule::unique('roles', 'name')->ignore($role->id),
                Rule::notIn([UserRole::SUPER_ADMIN->value, UserRole::BRAND->value, UserRole::MARKETER->value]),
            ];
        }

        $validated = $request->validate($rules, [
            'name.regex' => 'يجب أن يتكون الاسم الرمزي للدور من حروف إنجليزية كبيرة وأرقام وشرطة سفلية فقط.',
            'name.unique' => 'الاسم الرمزي للدور مستخدم مسبقاً.',
        ]);

        $oldData = [
            'name' => $role->name,
            'label' => $role->label,
            'permissions_count' => $role->permissions()->count(),
        ];

        $updateData = [
            'label' => trim($validated['label']),
            'description' => $validated['description'] ?? null,
        ];

        if (!$role->is_system && isset($validated['name'])) {
            $updateData['name'] = Str::upper(trim($validated['name']));
        }

        $role->update($updateData);

        // System roles like Brand and Marketer shouldn't have admin permissions assigned
        if (!in_array($role->name, [UserRole::BRAND->value, UserRole::MARKETER->value], true)) {
            $role->permissions()->sync($validated['permissions'] ?? []);
        }

        AuditService::log(
            action: AuditAction::ROLE_UPDATED,
            userId: $request->user()->id,
            entityType: 'Role',
            entityId: $role->id,
            payloadDiff: [
                'old' => $oldData,
                'new' => [
                    'name' => $role->name,
                    'label' => $role->label,
                    'permissions_count' => count($validated['permissions'] ?? []),
                ],
            ],
            status: 'SUCCESS',
            reason: 'تحديث بيانات وصلاحيات الدور الإداري'
        );

        return redirect()->route('admin.roles.index')
            ->with('status', "تم تحديث الدور ({$role->label}) وصلاحياته بنجاح.");
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Request $request, Role $role): RedirectResponse
    {
        // Enforce strict protection: System roles & Super Admin can never be deleted
        if ($role->is_system || $role->name === UserRole::SUPER_ADMIN->value) {
            abort(403, 'لا يمكن حذف الأدوار الأساسية المحمية للنظام.');
        }

        // Prevent deletion if assigned to users
        if ($role->users()->exists()) {
            return back()->with('error', "لا يمكن حذف الدور ({$role->label}) لأنه مسند حالياً إلى مستخدمين في النظام. يرجى إلغاء إسناد الدور من المستخدمين أولاً.");
        }

        $roleName = $role->label;
        $roleId = $role->id;
        $role->delete();

        AuditService::log(
            action: AuditAction::ROLE_DELETED,
            userId: $request->user()->id,
            entityType: 'Role',
            entityId: $roleId,
            payloadDiff: ['deleted_role' => $roleName],
            status: 'SUCCESS',
            reason: "حذف الدور الإداري ({$roleName})"
        );

        return redirect()->route('admin.roles.index')
            ->with('status', "تم حذف الدور الإداري ({$roleName}) بنجاح.");
    }
}
