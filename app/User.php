<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'users';

    protected $primaryKey = 'username';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = true;

    protected $fillable = [
        'username',
        'name',
        'pw',
        'depot_code',
        'role_code',
        'permissions',
        'is_active',
        'is_super_admin',
        'is_hq',
        'metadata',
    ];

    protected $hidden = ['pw'];

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
        return $this->pw;
    }

    public function setPwAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (str_starts_with($value, '$2y$') || str_starts_with($value, '$argon2i$') || str_starts_with($value, '$argon2id$')) {
            $this->attributes['pw'] = $value;

            return;
        }

        $this->attributes['pw'] = Hash::make($value);
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->is_super_admin;
    }
}
