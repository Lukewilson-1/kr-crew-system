<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrewStatusSegment extends Model
{
    use SoftDeletes;

    protected $table = 'crew_status_segments';

    protected $primaryKey = 'segment_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = true;

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
        'day' => 'integer',
        'sort_order' => 'integer',
        'rest_started_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function crewRecord(): BelongsTo
    {
        return $this->belongsTo(CrewRecord::class, 'crew_record_id', 'record_id');
    }

    public function getStatusCodeResolvedAttribute(): string
    {
        return (string) ($this->status_code ?: $this->status ?: '');
    }
}
