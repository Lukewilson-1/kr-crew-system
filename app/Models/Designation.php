<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends Model
{
    use SoftDeletes;

    protected $table = 'designations';
    protected $primaryKey = 'designation_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'designation_code',
        'designation_name',
        'sort_order',
        'is_active',
        'metadata',
    ];
}
