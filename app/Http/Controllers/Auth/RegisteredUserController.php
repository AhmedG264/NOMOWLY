<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AuditAction;
use App\Enums\MarketerSpecializationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\BrandProfile;
use App\Models\MarketerProfile;
use App\Models\MarketerSpecialization;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', [
            'specializations' => MarketerSpecializationType::cases(),
            'currencies' => config('nomowly.currencies', []),
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Strict Role Validation: Public registration ONLY allows BRAND or MARKETER
        $role = $request->input('role');
        if ($role === UserRole::SUPER_ADMIN->value) {
            abort(403, 'غير مصرح بتسجيل حساب مدير نظام عبر نموذج التسجيل العام.');
        }

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(UserRole::publicRegisterable())],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],

            // Brand specific fields
            'company_name' => [Rule::requiredIf($role === UserRole::BRAND->value), 'nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'currency' => [Rule::requiredIf($role === UserRole::BRAND->value), 'nullable', 'string', Rule::in(array_keys(config('nomowly.currencies', [])))],

            // Marketer specific fields
            'primary_specialization' => [
                Rule::requiredIf($role === UserRole::MARKETER->value),
                'nullable',
                'string',
                Rule::in(MarketerSpecializationType::values()),
            ],
            'specializations' => ['nullable', 'array'],
            'specializations.*' => ['string', Rule::in(MarketerSpecializationType::values())],
        ]);

        $user = DB::transaction(function () use ($validated, $role) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => UserRole::from($role),
                'status' => UserStatus::ACTIVE,
                'must_change_password' => false,
            ]);

            if ($role === UserRole::BRAND->value) {
                BrandProfile::create([
                    'user_id' => $user->id,
                    'company_name' => $validated['company_name'] ?? $validated['name'],
                    'website_url' => $validated['website_url'] ?? null,
                    'currency' => $validated['currency'] ?? config('nomowly.default_currency', 'EGP'),
                ]);

                AuditService::log(
                    action: AuditAction::REGISTER_BRAND,
                    userId: $user->id,
                    entityType: 'User',
                    entityId: $user->id,
                    payloadDiff: [
                        'company_name' => $validated['company_name'] ?? $validated['name'],
                        'currency' => $validated['currency'] ?? 'EGP',
                    ]
                );
            } elseif ($role === UserRole::MARKETER->value) {
                $marketerProfile = MarketerProfile::create([
                    'user_id' => $user->id,
                    'full_name' => $validated['name'],
                ]);

                $primarySpec = $validated['primary_specialization'];
                $allSpecs = collect($validated['specializations'] ?? [])
                    ->push($primarySpec)
                    ->unique()
                    ->values();

                foreach ($allSpecs as $spec) {
                    MarketerSpecialization::create([
                        'marketer_profile_id' => $marketerProfile->id,
                        'specialization' => MarketerSpecializationType::from($spec),
                        'is_primary' => ($spec === $primarySpec),
                    ]);
                }

                AuditService::log(
                    action: AuditAction::REGISTER_MARKETER,
                    userId: $user->id,
                    entityType: 'User',
                    entityId: $user->id,
                    payloadDiff: [
                        'primary_specialization' => $primarySpec,
                        'all_specializations' => $allSpecs->toArray(),
                    ]
                );
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        AuditService::log(
            action: AuditAction::LOGIN_SUCCESS,
            userId: $user->id,
            entityType: 'User',
            entityId: $user->id
        );

        if ($user->role === UserRole::BRAND) {
            return redirect()->route('brand.dashboard')->with('status', 'مرحباً بك في منصة نمولي! تم إنشاء حساب علامتك التجارية بنجاح.');
        }

        return redirect()->route('marketer.dashboard')->with('status', 'مرحباً بك في منصة نمولي! تم إنشاء حساب المسوق بنجاح.');
    }
}
