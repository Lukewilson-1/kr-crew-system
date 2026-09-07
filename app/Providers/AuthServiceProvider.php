<?php

namespace App\Providers;

use App\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Depot::class => \App\Policies\DepotPolicy::class,
        \App\Models\CrewRecord::class => \App\Policies\CrewRecordPolicy::class,
        \App\CrewMember::class => \App\Policies\CrewMemberPolicy::class,
        \App\Models\Room::class => \App\Policies\RoomPolicy::class,
        \App\Models\AttendanceRecord::class => \App\Policies\AttendanceRecordPolicy::class,
        \App\Models\Matter::class => \App\Policies\MatterPolicy::class,
        \App\Models\ShiftTemplate::class => \App\Policies\ShiftTemplatePolicy::class,
        \App\Models\Designation::class => \App\Policies\DesignationPolicy::class,
        \App\Models\TrainType::class => \App\Policies\TrainTypePolicy::class,
        \App\Models\Region::class => \App\Policies\RegionPolicy::class,
        \App\Models\StatusCode::class => \App\Policies\StatusCodePolicy::class,
        \App\Models\ReportDefinition::class => \App\Policies\ReportPolicy::class,
        \App\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Role::class => \App\Policies\RolePolicy::class,
        \App\Models\RestLocation::class => \App\Policies\RestLocationPolicy::class,
        \App\Models\DutyRoster::class => \App\Policies\DutyRosterPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Super admins bypass all permission checks.
        Gate::before(function (User $user, string $ability) {
            if ($user->is_super_admin) {
                return true;
            }

            return null; // Let policies decide.
        });
    }
}
