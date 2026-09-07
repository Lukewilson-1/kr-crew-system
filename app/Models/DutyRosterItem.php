<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DutyRosterItem extends Model
{
    use SoftDeletes;

    protected $table = 'duty_roster_items';

    protected $primaryKey = 'item_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'item_id',
        'roster_id',
        'crew_record_id',
        'crew_id',
        'shift_code',
        'train_type_code',
        'route_code',
        'rest_location_code',
        'duty_date',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'duty_date' => 'date',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (blank($model->item_id)) {
                $model->item_id = (string) Str::uuid();
            }
        });
    }

    public function roster()
    {
        return $this->belongsTo(DutyRoster::class, 'roster_id', 'roster_id');
    }

    public function crewMember()
    {
        return $this->belongsTo(CrewMember::class, 'crew_record_id', 'record_id');
    }
}
