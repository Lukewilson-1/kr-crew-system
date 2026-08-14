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
        'pw',
        'depot_code',
        'role_code',
        'permissions',
        'is_active',
        'is_super_admin',
        'is_hq',
        'metadata',
    ];

    protected $hidden = ['password', 'pw'];

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
        return (string) ($this->getAttribute('password') ?: $this->getAttribute('pw') ?: '');
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

        $permissions = $this->permissions;

        if (is_array($permissions) && in_array($permission, $permissions, true)) {
            return true;
        }

        return false;
    }

    public function passwordMatches(string $plainPassword): bool
    {
        $canonicalHash = (string) ($this->getAttribute('password') ?? '');
        $legacyHash = (string) ($this->getAttribute('pw') ?? '');
        $candidateHashes = array_values(array_filter([$canonicalHash, $legacyHash]));

        foreach ($candidateHashes as $stored) {
            if ($stored === '') {
                continue;
            }

            if (Hash::check($plainPassword, $stored)) {
                if ($canonicalHash === '' && $legacyHash !== '') {
                    $this->forceFill(['password' => $stored, 'pw' => $stored]);
                    $this->saveQuietly();
                }

                return true;
            }
        }

        return false;
    }

    public function setPasswordAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $hashed = $this->isPasswordHash($value) ? $value : Hash::make($value);
        $this->attributes['password'] = $hashed;
        $this->attributes['pw'] = $hashed;
    }

    public function setPwAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if ($this->isPasswordHash($value)) {
            $this->attributes['pw'] = $value;
            $this->attributes['password'] ??= $value;

            return;
        }

        $hashed = Hash::make($value);
        $this->attributes['pw'] = $hashed;
        $this->attributes['password'] ??= $hashed;
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

    public function canAccessPanel(Panel $panel): bool
    {
        $result = $this->is_active && $this->isGlobalAccess();
        \Log::debug('canAccessPanel called', [
            'username' => $this->username,
            'role_code' => $this->role_code,
            'depot_code' => $this->depot_code,
            'is_active' => $this->is_active,
            'is_hq' => $this->is_hq,
            'is_super_admin' => $this->is_super_admin,
            'result' => $result,
        ]);
        return $result;
    }
}
