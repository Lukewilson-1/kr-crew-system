<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportDefinition extends Model
{
    protected $table = 'reports';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'type',
        'route_name',
        'action_label',
        'category',
        'is_active',
        'sort_order',
    ];
}
