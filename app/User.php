<?php

namespace App;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'users';

    protected $primaryKey = 'username';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->username) && filled($model->email)) {
                $model->username = (string) str($model->email)->before('@')->slug('_')->value();
            }
        });
    }

    public $timestamps = true;

    protected $fillable = [
        'username',
        'email',
        'name',
        'password',
        'depot_code',
        'role_code',
        'permissions',
        'is_active',
        'is_super_admin',
        'is_hq',
        'metadata',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'is_super_admin' => 'boolean',
        'is_hq' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_code', 'role_code');
    }

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles',
            'username',
            'role_code',
            'username',
            'role_code'
        );
    }

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'user_permissions',
            'username',
            'permission_code',
            'username',
            'permission_code'
        );
    }

    public function getAuthPassword(): string
    {
        return (string) $this->getAttribute('password') ?: '';
    }

    public function getAuthIdentifierName(): string
    {
        return 'username';
    }

    public function getAuthIdentifier(): string
    {
        return (string) $this->getAttribute('username') ?? '';
    }

    public function hasPermissionTo(string $permission): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        // 1. Check direct JSON permissions column (legacy compat).
        $directPermissions = $this->permissions;
        if (is_array($directPermissions) && in_array($permission, $directPermissions, true)) {
            return true;
        }

        // 2. Check user_permissions pivot table.
        if ($this->permissions()->where('user_permissions.permission_code', $permission)->exists()) {
            return true;
        }

        // 3. Check role_permissions pivot table via the user's primary role.
        if ($this->role_code !== null) {
            $hasViaRole = \Illuminate\Support\Facades\DB::table('role_permissions')
                ->where('role_code', $this->role_code)
                ->where('permission_code', $permission)
                ->exists();
            if ($hasViaRole) {
                return true;
            }
        }

        // 4. Check any additional roles from user_roles pivot.
        $roleCodes = $this->roles()->pluck('roles.role_code');
        if ($roleCodes->isNotEmpty()) {
            $hasViaPivotRole = \Illuminate\Support\Facades\DB::table('role_permissions')
                ->whereIn('role_code', $roleCodes)
                ->where('permission_code', $permission)
                ->exists();
            if ($hasViaPivotRole) {
                return true;
            }
        }

        return false;
    }

    public function hasRole(string ...$roleCodes): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return in_array($this->role_code, $roleCodes, true)
            || $this->roles()->whereIn('roles.role_code', $roleCodes)->exists();
    }

    public function passwordMatches(string $plainPassword): bool
    {
        $storedHash = (string) ($this->getAttribute('password') ?? '');
        if ($storedHash === '') {
            return false;
        }

        return Hash::check($plainPassword, $storedHash);
    }

    public function setPasswordAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $hashed = $this->isPasswordHash($value) ? $value : Hash::make($value);
        $this->attributes['password'] = $hashed;
    }

    private function isPasswordHash(string $value): bool
    {
        return str_starts_with($value, '$2y$')
            || str_starts_with($value, '$argon2i$')
            || str_starts_with($value, '$argon2id$');
    }

    public function isAttendant(): bool
    {
        return $this->role === 'attendant' && filled($this->room_id);
    }

    public function isRoomAdmin(): bool
    {
        return $this->is_active && (
            $this->is_super_admin ||
            $this->is_hq ||
            $this->role_code === 'hq_admin' ||
            strtolower((string) $this->depot_code) === 'hq' ||
            $this->role === 'admin'
        );
    }

    public function getRememberTokenName(): ?string
    {
        return 'remember_token';
    }

    public function canAccessCrewSystem(): bool
    {
        return (bool) $this->is_active;
    }

    public function canAccessRunningRooms(): bool
    {
        return $this->is_active && (
            in_array($this->role_code, ['booking_officer', 'station_officer'], true) ||
            $this->isGlobalAccess()
        );
    }

    public function isGlobalAccess(): bool
    {
        return (bool) ($this->is_super_admin || $this->is_hq || $this->role_code === 'hq_admin' || $this->depot_code === 'HQ');
    }

    public function hasSystemConsolePermission(): bool
    {
        if ($this->isGlobalAccess()) {
            return true;
        }

        if ($this->permissions()->exists()) {
            return true;
        }

        if ($this->role_code !== null && \Illuminate\Support\Facades\DB::table('role_permissions')->where('role_code', $this->role_code)->exists()) {
            return true;
        }

        return $this->roles()->whereHas('permissions')->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->hasSystemConsolePermission();
    }
}
