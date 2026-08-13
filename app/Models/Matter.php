<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Matter extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id', 'date', 'category', 'description',
        'reported_by', 'status', 'resolved_date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'resolved_date' => 'date',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
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
