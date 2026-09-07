<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DutyRoster extends Model
{
    use SoftDeletes;

    protected $table = 'duty_rosters';

    protected $primaryKey = 'roster_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'roster_id',
        'depot_code',
        'roster_date',
        'period_label',
        'status',
        'metadata',
    ];

    protected $casts = [
        'roster_date' => 'date',
        'status' => 'string',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (blank($model->roster_id)) {
                $model->roster_id = (string) Str::uuid();
            }
        });
    }

    public function depot()
    {
        return $this->belongsTo(Depot::class, 'depot_code', 'depot_code');
    }

    public function items()
    {
        return $this->hasMany(DutyRosterItem::class, 'roster_id', 'roster_id');
    }
}
