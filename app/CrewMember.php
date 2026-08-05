<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrewMember extends Model
{
    use HasFactory;

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
