<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use RuntimeException;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * Primary key configuration
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     * Note: 'role' is kept mass-assignable for internal services, but guarded against tampering in auth/profile controllers.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'must_change_password',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /**
     * Dynamic RBAC: Roles assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    /**
     * Dynamic RBAC: Check if user has a specific role.
     * Seamlessly checks both dynamic roles and legacy enum value.
     */
    public function hasRole(string|Role $role): bool
    {
        $roleName = $role instanceof Role ? $role->name : strtoupper(trim($role));

        // 1. Direct legacy enum check
        $legacyVal = $this->role instanceof UserRole ? $this->role->value : (string) $this->role;
        if ($legacyVal === $roleName) {
            return true;
        }

        // 2. Dynamic RBAC relationship check
        return $this->roles->contains('name', $roleName);
    }

    /**
     * Check if user has any of the specified roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dynamic RBAC: Check if user has a specific permission.
     * Super Admin holds all permissions unconditionally.
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check permissions through loaded roles or query
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign a role to the user with security guard against unauthorized Super Admin promotion.
     */
    public function assignRole(string|Role $role, ?User $assignedBy = null): void
    {
        $roleModel = $role instanceof Role ? $role : Role::where('name', strtoupper(trim($role)))->firstOrFail();

        // Security check: Only an active Super Admin can assign SUPER_ADMIN role
        if ($roleModel->name === UserRole::SUPER_ADMIN->value && (!$assignedBy || !$assignedBy->isSuperAdmin())) {
            throw new RuntimeException('غير مصرح لك بمنح رتبة المدير العام (Super Admin).');
        }

        if (!$this->roles->contains('id', $roleModel->id)) {
            $this->roles()->attach($roleModel->id, [
                'assigned_by' => $assignedBy?->id,
            ]);

            // Keep legacy role column in sync if assigning system roles
            if (in_array($roleModel->name, [UserRole::SUPER_ADMIN->value, UserRole::BRAND->value, UserRole::MARKETER->value], true)) {
                $this->update(['role' => UserRole::from($roleModel->name)]);
            }
        }
    }

    /**
     * Sync roles assigned to the user.
     */
    public function syncRoles(array $roleIds, ?User $assignedBy = null): void
    {
        $roles = Role::whereIn('id', $roleIds)->get();

        // Security check: If target includes SUPER_ADMIN, assigner MUST be Super Admin
        if ($roles->contains('name', UserRole::SUPER_ADMIN->value) && (!$assignedBy || !$assignedBy->isSuperAdmin())) {
            throw new RuntimeException('غير مصرح لك بمنح رتبة المدير العام (Super Admin).');
        }

        // Map pivot data with UUIDs
        $syncData = [];
        foreach ($roles as $r) {
            $syncData[$r->id] = [
                'assigned_by' => $assignedBy?->id,
            ];
        }

        $this->roles()->sync($syncData);

        // Keep legacy role column in sync with primary role
        $primarySystemRole = $roles->firstWhere('is_system', true);
        if ($primarySystemRole && in_array($primarySystemRole->name, [UserRole::SUPER_ADMIN->value, UserRole::BRAND->value, UserRole::MARKETER->value], true)) {
            $this->update(['role' => UserRole::from($primarySystemRole->name)]);
        } elseif ($roles->isNotEmpty()) {
            // If assigned administrative role (e.g. OPERATIONS_MANAGER), preserve or adapt
            if ($roles->contains('name', UserRole::SUPER_ADMIN->value)) {
                $this->update(['role' => UserRole::SUPER_ADMIN]);
            }
        }
    }

    /**
     * Backward-compatible helper methods
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN || $this->roles->contains('name', UserRole::SUPER_ADMIN->value);
    }

    public function isBrand(): bool
    {
        return $this->role === UserRole::BRAND || $this->roles->contains('name', UserRole::BRAND->value);
    }

    public function isMarketer(): bool
    {
        return $this->role === UserRole::MARKETER || $this->roles->contains('name', UserRole::MARKETER->value);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    public function brandProfile(): HasOne
    {
        return $this->hasOne(BrandProfile::class);
    }

    public function marketerProfile(): HasOne
    {
        return $this->hasOne(MarketerProfile::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function getWallet(string|Currency $currency): ?Wallet
    {
        $code = $currency instanceof Currency ? $currency->value : $currency;
        return $this->wallets()->where('currency', $code)->first();
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(PayoutRequest::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MarketerSubscription::class)->latest('created_at');
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(MarketerSubscription::class)
            ->where('status', \App\Enums\SubscriptionStatus::ACTIVE)
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->latest('starts_at');
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function currentSubscription(): ?MarketerSubscription
    {
        return $this->activeSubscription;
    }
}
