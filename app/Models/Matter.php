<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon as CarbonInterface;

class Matter extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id', 'date', 'category', 'description',
        'reported_by', 'status', 'resolved_date', 'ticket_no',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'resolved_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Matter $matter) {
            if (! $matter->ticket_no) {
                $matter->ticket_no = static::nextTicketNo($matter->date ?? now());
            }
        });
    }

    /**
     * Next sequential ticket number in the format MTR-<year>-<4-digit seq>.
     * Sequential per year so numbers stay short and readable while tracking.
     */
    public static function nextTicketNo(string|CarbonInterface $date): string
    {
        $year = ($date instanceof CarbonInterface) ? $date->year : substr($date, 0, 4);
        $prefix = 'MTR-'.$year.'-';

        $last = static::query()
            ->where('ticket_no', 'like', $prefix.'%')
            ->orderByDesc('ticket_no')
            ->value('ticket_no');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(MatterPhoto::class);
    }

    public function resolve(): void
    {
        $this->update(['status' => 'resolved', 'resolved_date' => now()->toDateString()]);
    }

    public function reopen(): void
    {
        $this->update(['status' => 'open', 'resolved_date' => null]);
    }
}
