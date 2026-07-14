<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'permissions';

    protected $primaryKey = 'permission_code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'permission_code',
        'permission_name',
        'description',
        'is_system',
        'metadata',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'metadata' => 'array',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'role_permissions',
            'permission_code',
            'role_code',
            'permission_code',
            'role_code'
        );
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_permissions',
            'permission_code',
            'username',
            'permission_code',
            'username'
        );
    }
}
