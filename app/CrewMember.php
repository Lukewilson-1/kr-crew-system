<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrewMember extends Model
{
    use HasFactory;

    protected $table = 'crew_members';

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
