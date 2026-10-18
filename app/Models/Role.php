<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use RuntimeException;

class Role extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'label',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Enforce strict immutability and protection of Super Admin and Core System Roles.
     */
    protected static function booted(): void
    {
        static::updating(function (Role $role) {
            // Cannot rename or alter system roles, especially SUPER_ADMIN
            if ($role->getOriginal('name') === UserRole::SUPER_ADMIN->value && $role->name !== UserRole::SUPER_ADMIN->value) {
                throw new RuntimeException('لا يمكن تغيير مسمى دور المدير العام (SUPER_ADMIN) نهائياً.');
            }
            if ($role->getOriginal('is_system') && $role->isDirty('name')) {
                throw new RuntimeException('الأدوار الأساسية للنظام محمية ولا يمكن تغيير معرّفها البرمجي.');
            }
        });

        static::deleting(function (Role $role) {
            if ($role->is_system || $role->name === UserRole::SUPER_ADMIN->value) {
                throw new RuntimeException('الأدوار الأساسية للنظام (Super Admin, Brand, Marketer) محمية برمجياً ولا يمكن حذفها.');
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withTimestamps();
    }

    public function hasPermission(string $permissionName): bool
    {
        if ($this->name === UserRole::SUPER_ADMIN->value) {
            return true;
        }

        return $this->permissions->contains('name', $permissionName);
    }

    public function isSuperAdmin(): bool
    {
        return $this->name === UserRole::SUPER_ADMIN->value;
    }

    public function isProtected(): bool
    {
        return (bool) $this->is_system || $this->isSuperAdmin();
    }
}
