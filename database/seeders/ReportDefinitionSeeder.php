<?php

namespace Database\Seeders;

use App\Models\ReportDefinition;
use Illuminate\Database\Seeder;

class ReportDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'name' => 'Daily Status Export',
                'slug' => 'daily-status-export',
                'description' => 'Download the current crew status snapshot for the active depot view.',
                'icon' => '📊',
                'type' => 'export',
                'report_type' => 'status',
                'route_name' => 'reports.daily-status',
                'action_label' => 'Export current status',
                'category' => 'Crew Management',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Monthly Register',
                'slug' => 'monthly-register',
                'description' => 'Download the current month roster with daily status codes for every crew member.',
                'icon' => '📅',
                'type' => 'export',
                'report_type' => 'monthly',
                'route_name' => 'reports.monthly-register',
                'action_label' => 'Download monthly register',
                'category' => 'Crew Management',
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'name' => 'Utilization Report',
                'slug' => 'utilization-report',
                'description' => 'Review booked-day utilization over a selected time window.',
                'icon' => '📈',
                'type' => 'report',
                'report_type' => 'utilization',
                'route_name' => 'reports.utilization',
                'action_label' => 'Export utilization',
                'category' => 'Operations',
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'name' => 'Absence / NTB Report',
                'slug' => 'absence-ntb-report',
                'description' => 'Export staff currently on leave, sick, absent, or marked NTB.',
                'icon' => '⚠️',
                'type' => 'export',
                'report_type' => 'absence',
                'route_name' => 'reports.absence',
                'action_label' => 'Export absence report',
                'category' => 'Operations',
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'name' => 'Printable Register',
                'slug' => 'printable-register',
                'description' => 'Open the monthly register view for printing.',
                'icon' => '🖨️',
                'type' => 'view',
                'report_type' => 'print',
                'route_name' => 'reports.printable',
                'action_label' => 'Open printable view',
                'category' => 'Printing',
                'is_active' => true,
                'sort_order' => 50,
            ],
        ];

        foreach ($defaults as $definition) {
            ReportDefinition::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                $definition
            );
        }
    }
}
