<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    /**
     * Show the mandatory password change view.
     */
    public function show(): View
    {
        return view('auth.password-change');
    }

    /**
     * Handle updating the mandatory password change.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()->symbols()],
        ]);

        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();

        AuditService::log(
            action: AuditAction::PASSWORD_CHANGE,
            userId: $user->id,
            entityType: 'User',
            entityId: $user->id,
            payloadDiff: [
                'reason' => 'Mandatory initial password change completed',
            ]
        );

        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.dashboard')->with('status', 'تم تغيير كلمة المرور بنجاح. مرحباً بك في لوحة تحكم إدارة النظام.');
        }

        if ($user->isBrand()) {
            return redirect()->route('brand.dashboard')->with('status', 'تم تغيير كلمة المرور بنجاح.');
        }

        return redirect()->route('marketer.dashboard')->with('status', 'تم تغيير كلمة المرور بنجاح.');
    }
}
