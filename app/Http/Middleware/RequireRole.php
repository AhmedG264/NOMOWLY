<?php

namespace App\Http\Middleware;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    /**
     * Handle an incoming request with Dynamic RBAC support.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Check if user has any of the authorized roles via Dynamic RBAC or legacy enum
        if ($user->hasAnyRole($roles)) {
            return $next($request);
        }

        // Log unauthorized access attempt in Audit Log
        AuditService::log(
            action: AuditAction::UNAUTHORIZED_ACCESS_ATTEMPT,
            userId: $user->id,
            entityType: 'RoleRestrictedRoute',
            entityId: $request->path(),
            payloadDiff: [
                'required_roles' => $roles,
                'user_role' => $user->role instanceof UserRole ? $user->role->value : (string) $user->role,
                'assigned_roles' => $user->roles->pluck('name')->toArray(),
            ],
            status: 'UNAUTHORIZED',
            reason: 'محاولة وصول بدون الدور المطلوب'
        );

        abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
    }
}
