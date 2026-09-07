<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissions = [
            ['permission_code' => 'manage_running_rooms', 'permission_name' => 'Manage Running Rooms', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['permission_code' => 'manage_duty_rosters', 'permission_name' => 'Manage Duty Rosters', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['permission_code' => 'manage_rest_locations', 'permission_name' => 'Manage Rest Locations', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('permissions')->insertOrIgnore($permissions);

        // Grant manage_running_rooms to station_officer and booking_officer.
        $runningRoomsRoles = ['station_officer', 'booking_officer'];
        foreach ($runningRoomsRoles as $roleCode) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_code' => $roleCode,
                'permission_code' => 'manage_running_rooms',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Grant manage_duty_rosters to station_officer, booking_officer, crew_admin.
        $dutyRosterRoles = ['station_officer', 'booking_officer', 'crew_admin'];
        foreach ($dutyRosterRoles as $roleCode) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_code' => $roleCode,
                'permission_code' => 'manage_duty_rosters',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Grant manage_rest_locations to station_officer, booking_officer.
        foreach ($runningRoomsRoles as $roleCode) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_code' => $roleCode,
                'permission_code' => 'manage_rest_locations',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->whereIn('permission_code', [
            'manage_running_rooms',
            'manage_duty_rosters',
            'manage_rest_locations',
        ])->delete();

        DB::table('permissions')->whereIn('permission_code', [
            'manage_running_rooms',
            'manage_duty_rosters',
            'manage_rest_locations',
        ])->delete();
    }
};
