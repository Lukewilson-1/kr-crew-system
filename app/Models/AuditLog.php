<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'audit_logs';

    protected $fillable = [
        'actor_username',
        'actor_ip',
        'actor_user_agent',
        'event',
        'entity_type',
        'entity_id',
        'before',
        'after',
        'changes',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'changes' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];
}