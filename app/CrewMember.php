<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CrewMember extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->record_id)) {
                $candidate = null;

                if (! empty($model->staff_number)) {
                    $candidate = (string) Str::of($model->staff_number)
                        ->trim()
                        ->replaceMatches('/[^A-Za-z0-9_-]+/', '_')
                        ->upper();
                } elseif (! empty($model->crew_id)) {
                    $candidate = (string) Str::of($model->crew_id)
                        ->trim()
                        ->replaceMatches('/[^A-Za-z0-9_-]+/', '_')
                        ->upper();
                }

                $model->record_id = $candidate ?: (string) Str::uuid();
            }
         });
     }
 
     protected $table = 'crew_members';

    // The crew_members table uses `record_id` as the primary key (string), not the default `id`.
    protected $primaryKey = 'record_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'record_id',
        'crew_id',
        'staff_number',
        'depot_code',
        'first_name',
        'last_name',
        'display_name',
        'designation_code',
        'employment_status_code',
        'hire_date',
        'phone',
        'email',
        'is_active',
        'metadata',
    ];
}
