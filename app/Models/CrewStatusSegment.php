<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrewStatusSegment extends Model
{
    protected $table = 'crew_status_segments';

    protected $fillable = [
        'segment_id',
        'crew_record_id',
        'crew_id',
        'depot_code',
        'month_key',
        'day',
        'date',
        'status',
        'status_code',
        'start_time',
        'end_time',
        'note',
        'train_type',
        'route',
        'book_time',
        'rest_started_at',
        'away_depot',
        'notes',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
