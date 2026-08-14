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
        'report_type',
        'route_name',
        'action_label',
        'category',
        'is_active',
        'sort_order',
        'builder_layout',
        'builder_columns',
        'builder_filters',
        'builder_group_by',
    ];

    protected $casts = [
        'builder_columns' => 'array',
        'builder_filters' => 'array',
        'is_active' => 'boolean',
    ];
}
