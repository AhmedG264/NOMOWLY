<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($request->only('email', 'password'), $remember)) {
            RateLimiter::hit($this->throttleKey($request), 60);

            AuditService::log(
                action: AuditAction::LOGIN_FAILED,
                userId: null,
                entityType: 'User',
                entityId: null,
                payloadDiff: ['attempted_email' => $request->input('email')],
                status: 'FAILED',
                reason: 'بيانات الاعتماد المدخلة غير صحيحة'
            );

            throw ValidationException::withMessages([
                'email' => __('بيانات الاعتماد المدخلة غير صحيحة، يرجى المحاولة مرة أخرى.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        // Regenerate session to protect against session fixation attacks
        $request->session()->regenerate();

        $user = Auth::user();

        AuditService::log(
            action: AuditAction::LOGIN_SUCCESS,
            userId: $user->id,
            entityType: 'User',
            entityId: $user->id
        );

        // Enforce mandatory password change if flagged
        if ($user->must_change_password) {
            return redirect()->route('password.change')
                ->with('warning', 'يرجى تغيير كلمة المرور المؤقتة أولاً لتفعيل حسابك بالكامل.');
        }

        // Role-based post-login redirection
        if ($user->role === UserRole::SUPER_ADMIN) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if ($user->role === UserRole::BRAND) {
            return redirect()->intended(route('brand.dashboard'));
        }

        return redirect()->intended(route('marketer.dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            AuditService::log(
                action: AuditAction::LOGOUT,
                userId: $user->id,
                entityType: 'User',
                entityId: $user->id
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'تم تسجيل الخروج بنجاح.');
    }

    /**
     * Ensure the login request is not rate limited.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "لقد تجاوزت عدد محاولات الدخول المسموح بها. يرجى المحاولة بعد {$seconds} ثانية.",
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());
    }
}
