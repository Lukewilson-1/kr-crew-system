<?php

namespace App;

use App\Models\CrewStatusSegment;
use Illuminate\Database\Eloquent\Model;

class CrewRecord extends Model
{
    protected $table = 'crew_records';

    protected $casts = [
        'payload' => 'array',
    ];

    public function segments()
    {
        return $this->hasMany(CrewStatusSegment::class, 'crew_record_id', 'record_id');
    }

    public function getFinalStatusAttribute(): ?string
    {
        $segments = $this->segments()->orderBy('day')->orderBy('sort_order')->get();

        if ($segments->isEmpty()) {
            $payload = $this->payload ?? [];
            return $payload['status'] ?? null;
        }

        $last = $segments->last();

        return $last->status_code ?? $last->status ?? null;
    }
}
