<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomBed extends Model
{
    use SoftDeletes;

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

    /**
     * Batch occupancy lookup for a set of beds, avoiding a query per bed.
     * Returns a map of bed_no => occupied for the given room.
     *
     * @return array<string, bool>
     */
    public static function occupancyMap(int $roomId, iterable $bedNos): array
    {
        $occupied = array_flip(
            AttendanceRecord::query()
                ->where('room_id', $roomId)
                ->whereIn('bed_no', $bedNos)
                ->where('status', 'in')
                ->pluck('bed_no')
                ->all()
        );

        $map = [];
        foreach ($bedNos as $bedNo) {
            $map[$bedNo] = isset($occupied[$bedNo]);
        }

        return $map;
    }
}
