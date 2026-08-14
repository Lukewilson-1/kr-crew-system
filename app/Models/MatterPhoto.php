<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatterPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'matter_id', 'filename',
    ];

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }
}
