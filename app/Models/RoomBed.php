<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomBed extends Model
{
    protected $fillable = ['room_id', 'bed_no', 'is_usable'];

    protected function casts(): array
    {
        return ['is_usable' => 'boolean'];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function isOccupied(): bool
    {
        return AttendanceRecord::query()
            ->where('room_id', $this->room_id)
            ->where('bed_no', $this->bed_no)
            ->where('status', 'in')
            ->exists();
    }
}
