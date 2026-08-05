<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Depot extends Model
{
    use SoftDeletes;

    protected $table = 'depots';
    protected $primaryKey = 'depot_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'depot_code',
        'depot_name',
        'region',
        'color',
        'is_hq',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
