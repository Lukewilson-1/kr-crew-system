<?php

namespace App\Providers;

use App\CrewMember;
use App\Models\CrewRecord;
use App\Models\ReportDefinition;
use App\Observers\EloquentAuditObserver;
use App\Permission;
use App\Role;
use App\User;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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

        // Keep the maintenance sign-in and control page reachable while the site is
        // offline, and let the token-protected cron webhook keep running during
        // maintenance.
        PreventRequestsDuringMaintenance::except([
            'maintenance-login',
            'maintenance',
            'running-rooms/cron/auto-checkout',
        ]);

        // The maintenance bypass cookie is read by CheckForMaintenanceMode, which
        // runs in the global stack BEFORE the web group's EncryptCookies middleware
        // decrypts cookies. It must therefore never be encrypted, or the maintenance
        // sign-in would set a cookie that the next request cannot validate.
        EncryptCookies::except(['laravel_maintenance']);

        // The maintenance sign-in and control endpoints are the escape hatch during
        // outages, so they must keep working even when the session or CSRF token is
        // the very thing that is broken (a stale/expired session renders classic
        // "419 Page Expired" on the control form). They are pass-phrase gated and
        // every action is audited (operator, IP, user agent), so we explicitly
        // exempt them from CSRF rather than lose access to a downed site.
        VerifyCsrfToken::except([
            'maintenance-login',
            'maintenance',
        ]);

        // Keep a lightweight per-request maintenance flag so portal templates can
        // render the "system under maintenance" banner for the maintenance
        // operator for the entire time the site is down.
        View::composer('*', function ($view) {
            $view->with('maintenanceNotice', $this->maintenanceNotice());
        });

        $this->registerAuditObservers();

        $this->seedDefaultReports();
    }

    private function registerAuditObservers(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $sensitiveModels = [
            User::class,
            Role::class,
            Permission::class,
            CrewMember::class,
            CrewRecord::class,
        ];

        foreach ($sensitiveModels as $model) {
            $model::observe(EloquentAuditObserver::class);
        }
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

    /**
     * Maintenance state shared with every rendered view. Returns null when the
     * site is online, or [active, started_at, ends_at, timezone] while the site
     * is down so the portal templates can keep a banner visible for the entire
     * maintenance period for the maintenance operator.
     */
    private function maintenanceNotice(): ?array
    {
        if (! app()->maintenanceMode()->active()) {
            return null;
        }

        $data = [];
        try {
            $data = app()->maintenanceMode()->data();
        } catch (\Throwable) {
            $data = [];
        }

        $timezone = (string) config('maintenance.timezone');
        $timezone = $timezone !== '' ? $timezone : 'Africa/Nairobi';

        return [
            'active' => true,
            'started_at' => $data['started_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'timezone' => $timezone,
        ];
    }
}
