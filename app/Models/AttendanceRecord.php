<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id', 'name', 'staff_no', 'designation', 'bed_no',
        'arrival_date', 'arrival_time', 'departure_date', 'departure_time',
        'remarks', 'status',
    ];

    protected function casts(): array
    {
        return [
            'arrival_date' => 'date',
            'departure_date' => 'date',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function checkOut(?string $date = null, ?string $time = null): void
    {
        $this->update([
            'status' => 'out',
            'departure_date' => $date ?? now()->toDateString(),
            'departure_time' => $time ?? now()->format('H:i'),
        ]);
    }

    /** Was this guest occupying a bed on the given date? Mirrors wasOccupiedOn(). */
    public function wasOccupiedOn(string $date): bool
    {
        if ($this->arrival_date->toDateString() > $date) {
            return false;
        }

        $endDate = ($this->status === 'out' && $this->departure_date)
            ? $this->departure_date->toDateString()
            : now()->toDateString();

        return $date <= $endDate;
    }
}
