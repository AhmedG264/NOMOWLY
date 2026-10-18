<?php

namespace App\Http\Middleware;

use App\Enums\AuditAction;
use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    /**
     * Handle an incoming request checking for specific granular permissions.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Super Admin bypasses all checks; otherwise check granular permissions
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        // Log unauthorized permission attempt
        AuditService::log(
            action: AuditAction::UNAUTHORIZED_ACCESS_ATTEMPT,
            userId: $user->id,
            entityType: 'PermissionRestrictedRoute',
            entityId: $request->path(),
            payloadDiff: [
                'required_permissions' => $permissions,
                'user_id' => $user->id,
            ],
            status: 'UNAUTHORIZED',
            reason: 'محاولة وصول بدون امتلاك الصلاحية المطلوبة'
        );

        abort(403, 'غير مصرح لك بتنفيذ هذه العملية لعدم امتلاك الصلاحية المطلوبة.');
    }
}
