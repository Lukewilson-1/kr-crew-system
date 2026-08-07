<?php

namespace App\Providers;

use App\Models\ReportDefinition;
use Illuminate\Pagination\PaginationState;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        PaginationState::resolveUsing($this->app);

        View::addNamespace(
            'pagination',
            base_path('vendor/laravel/framework/src/Illuminate/Pagination/resources/views')
        );

        $this->seedDefaultReports();
    }

    private function seedDefaultReports(): void
    {
        if (! Schema::hasTable('reports')) {
            return;
        }
        // Only seed defaults when the reports table is empty so admin edits are not overwritten on every boot.
        if (ReportDefinition::query()->exists()) {
            return;
        }

        $defaults = [
            [
                'name' => 'Daily Status Export',
                'slug' => 'daily-status-export',
                'description' => 'Download the current crew status snapshot for the active depot view.',
                'icon' => '📊',
                'type' => 'export',
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
