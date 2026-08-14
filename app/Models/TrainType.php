<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainType extends Model
{
    use SoftDeletes;

    protected $table = 'train_types';
    protected $primaryKey = 'train_type_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'train_type_code',
        'train_type_name',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];
}
