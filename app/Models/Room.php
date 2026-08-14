<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'depot_code', 'beds'];

    protected $hidden = [];

    protected function casts(): array
    {
        return ['beds' => 'integer'];
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function roomBeds(): HasMany
    {
        return $this->hasMany(RoomBed::class);
    }

    public function usableBeds(): HasMany
    {
        return $this->roomBeds()->where('is_usable', true);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'room_id');
    }

    public function matters(): HasMany
    {
        return $this->hasMany(Matter::class);
    }

    /** Guests currently checked in (mirrors currentlyIn() in the original app). */
    public function currentlyIn(): HasMany
    {
        return $this->attendanceRecords()->where('status', 'in');
    }

    public function occupiedCount(): int
    {
        return $this->currentlyIn()->count();
    }

    public function vacantBeds(): int
    {
        return max(0, $this->beds - $this->occupiedCount());
    }

    public function isFull(): bool
    {
        return $this->vacantBeds() <= 0;
    }

    /**
     * Keep the per-bed rows aligned with the declared bed capacity. Adds beds
     * that are missing and removes (unreferenced) beds above the new capacity,
     * so a reduced capacity stops offering the excess beds.
     */
    public function syncBeds(): void
    {
        $count = max(0, (int) $this->beds);
        $wanted = [];

        for ($i = 1; $i <= $count; $i++) {
            $wanted['B'.$i] = true;
        }

        $existing = $this->roomBeds()->orderBy('bed_no')->get();

        foreach ($wanted as $bedNo => $unused) {
            if (! $existing->contains('bed_no', $bedNo)) {
                $this->roomBeds()->create(['bed_no' => $bedNo, 'is_usable' => true]);
            }
        }

        foreach ($existing as $bed) {
            if (! isset($wanted[$bed->bed_no])) {
                $referenced = AttendanceRecord::query()
                    ->where('room_id', $this->id)
                    ->where('bed_no', $bed->bed_no)
                    ->exists();

                if (! $referenced) {
                    $bed->delete();
                }
            }
        }
    }

    /** Bed numbers that are usable and unoccupied on the given date. */
    public function availableBedsOn(string $date): array
    {
        $occupied = AttendanceRecord::query()
            ->where('room_id', $this->id)
            ->whereNotNull('bed_no')
            ->where('arrival_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->where('status', 'in')
                    ->orWhere(function ($q2) use ($date) {
                        $q2->whereNull('departure_date')
                            ->orWhere('departure_date', '>=', $date);
                    });
            })
            ->pluck('bed_no')
            ->all();

        return $this->usableBeds()
            ->get()
            ->filter(fn (RoomBed $bed) => ! in_array($bed->bed_no, $occupied, true))
            ->sortBy(fn (RoomBed $bed) => [(int) preg_replace('/[^0-9]/', '', $bed->bed_no), $bed->bed_no])
            ->pluck('bed_no')
            ->values()
            ->all();
    }

    protected static function booted(): void
    {
        static::saved(function (Room $room) {
            $room->syncBeds();
        });
    }

}
