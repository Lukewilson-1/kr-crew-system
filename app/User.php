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
        return 'email';
    }

    public function getAuthIdentifier(): string
    {
        return (string) ($this->getAttribute('email') ?: $this->getAttribute('username') ?? '');
    }

    public function passwordMatches(string $plainPassword): bool
    {
        $candidateHashes = array_filter([
            (string) ($this->getAttribute('password') ?? ''),
            (string) ($this->getAttribute('pw') ?? ''),
        ]);

        foreach ($candidateHashes as $stored) {
            if ($stored === '') {
                continue;
            }

            if (Hash::check($plainPassword, $stored)) {
                return true;
            }

            if ($stored === $plainPassword) {
                $this->forceFill(['password' => Hash::make($plainPassword), 'pw' => Hash::make($plainPassword)]);
                $this->saveQuietly();

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

        $this->attributes['password'] = Hash::make($value);
        $this->attributes['pw'] = Hash::make($value);
    }

    public function setPwAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (str_starts_with($value, '$2y$') || str_starts_with($value, '$argon2i$') || str_starts_with($value, '$argon2id$')) {
            $this->attributes['pw'] = $value;
            $this->attributes['password'] = $value;

            return;
        }

        $hashed = Hash::make($value);
        $this->attributes['pw'] = $hashed;
        $this->attributes['password'] = $hashed;
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $result = $this->is_active && (
            $this->is_super_admin ||
            $this->is_hq ||
            $this->role_code === 'hq_admin' ||
            $this->depot_code === 'HQ'
        );
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
