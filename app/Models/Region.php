<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Region extends Model
{
    use SoftDeletes;

    protected $table = 'regions';
    protected $primaryKey = 'region_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->region_code)) {
                $slug = Str::slug($model->region_name ?: 'region');
                $candidate = $slug ?: (string) Str::uuid();
                $original = $candidate;
                $suffix = 1;

                while (self::query()->where('region_code', $candidate)->exists()) {
                    $candidate = $original.'-'.++$suffix;
                }

                $model->region_code = $candidate;
            }
        });
    }

    protected $fillable = [
        'region_code',
        'region_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
