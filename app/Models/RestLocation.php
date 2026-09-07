<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RestLocation extends Model
{
    use SoftDeletes;

    protected $table = 'rest_locations';

    protected $primaryKey = 'rest_location_code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'rest_location_code',
        'rest_location_name',
        'depot_code',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function depot()
    {
        return $this->belongsTo(Depot::class, 'depot_code', 'depot_code');
    }
}
