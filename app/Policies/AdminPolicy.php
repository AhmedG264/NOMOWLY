<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class AdminPolicy
{
    /**
     * Determine whether the user can access the Super Admin control center.
     */
    public function viewAdmin(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can manage brands.
     */
    public function manageBrands(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('brands.manage');
    }

    /**
     * Determine whether the user can manage marketers.
     */
    public function manageMarketers(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('marketers.manage');
    }

    /**
     * Determine whether the user can inspect audit logs.
     */
    public function viewAuditLogs(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('audit.view');
    }
}
