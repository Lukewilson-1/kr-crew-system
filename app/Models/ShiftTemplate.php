<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'shift_templates';
    protected $primaryKey = 'shift_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'shift_code',
        'shift_name',
        'starts_at',
        'ends_at',
        'sort_order',
        'is_active',
        'metadata',
    ];
}
