<?php

namespace App\Http\Middleware;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->role !== UserRole::SUPER_ADMIN) {
            AuditService::log(
                action: AuditAction::UNAUTHORIZED_ACCESS_ATTEMPT,
                userId: $user->id,
                entityType: 'SuperAdminRoute',
                entityId: $request->path(),
                payloadDiff: [
                    'attempted_path' => $request->path(),
                    'user_role' => $user->role->value,
                ]
            );

            abort(403, 'غير مصرح بالوصول إلى منطقة إدارة النظام.');
        }

        return $next($request);
    }
}
