<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function notice(): View
    {
        return view('auth.verify-email');
    }

    /**
     * Mark the authenticated user's email address as verified.
     */
    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            AuditService::log(
                action: AuditAction::EMAIL_VERIFIED,
                userId: $user->id,
                entityType: 'User',
                entityId: $user->id
            );
        }

        return redirect()->route('login')->with('status', 'تم تأكيد البريد الإلكتروني بنجاح.');
    }
}
