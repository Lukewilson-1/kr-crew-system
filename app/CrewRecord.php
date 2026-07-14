<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrewRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'staff_number',
        'depot',
        'designation',
        'status',
        'route',
        'notes',
    ];
}
