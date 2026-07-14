<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'roles';

    protected $primaryKey = 'role_code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'role_code',
        'role_name',
        'description',
        'is_system',
        'metadata',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'metadata' => 'array',
    ];

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_code',
            'permission_code',
            'role_code',
            'permission_code'
        );
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_roles',
            'role_code',
            'username',
            'role_code',
            'username'
        );
    }
}
