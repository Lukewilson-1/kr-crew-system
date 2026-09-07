<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StatusCode extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $incrementing = false;
    protected $primaryKey = 'status_code';
    protected $keyType = 'string';
    protected $table = 'status_codes';
    protected $fillable = ['status_code', 'status_label', 'sort_order', 'is_terminal', 'metadata'];
    protected $casts = [
        'metadata' => 'array',
        'is_terminal' => 'boolean',
    ];

    public $timestamps = false;

    public function getLabelAttribute(): string
    {
        return $this->status_label ?? $this->status_code;
    }
}
