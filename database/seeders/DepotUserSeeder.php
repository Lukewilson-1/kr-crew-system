<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DepotUserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = env('DEFAULT_USER_PASSWORD');

        $users = [
            ['username' => 'rsf_mkr', 'name' => 'Makadara', 'email' => 'rsfmkr@krc.co.ke', 'depot_code' => 'MKR', 'role_code' => 'booking_officer'],
            ['username' => 'rsf_cgw', 'name' => 'Changamwe', 'email' => 'rsfcgw@krc.co.ke', 'depot_code' => 'CGW', 'role_code' => 'booking_officer'],
            ['username' => 'rsf_mto', 'name' => 'MTITO', 'email' => 'rsfmto@krc.co.ke', 'depot_code' => 'MTO', 'role_code' => 'booking_officer'],
            ['username' => 'rsf_nro', 'name' => 'Nakuru', 'email' => 'rsfnro@krc.co.ke', 'depot_code' => 'NRO', 'role_code' => 'booking_officer'],
            ['username' => 'rsf_eld', 'name' => 'Eldoret', 'email' => 'rsfeld@krc.co.ke', 'depot_code' => 'ELD', 'role_code' => 'booking_officer'],
            ['username' => 'rsf_ksm', 'name' => 'Kisumu', 'email' => 'rsfksm@krc.co.ke', 'depot_code' => 'KSM', 'role_code' => 'booking_officer'],
            ['username' => 'rsf_mlb', 'name' => 'Malaba', 'email' => 'rsfmlb@krc.co.ke', 'depot_code' => 'MLB', 'role_code' => 'booking_officer'],
            ['username' => 'rsf_nuk', 'name' => 'Nanyuki', 'email' => 'rsfnuk@krc.co.ke', 'depot_code' => 'NUK', 'role_code' => 'booking_officer'],
            ['username' => 'stn_mkr', 'name' => 'Makadara Station Officer', 'email' => 'stationmastermkr@krc.co.ke', 'depot_code' => 'MKR', 'role_code' => 'station_officer'],
            ['username' => 'stn_cgw', 'name' => 'Changamwe Station Officer', 'email' => 'stationmastercgw@krc.co.ke', 'depot_code' => 'CGW', 'role_code' => 'station_officer'],
            ['username' => 'stn_mto', 'name' => 'MTITO Station Officer', 'email' => 'stationmastermto@krc.co.ke', 'depot_code' => 'MTO', 'role_code' => 'station_officer'],
            ['username' => 'stn_nro', 'name' => 'Nakuru Station Officer', 'email' => 'stationmasternro@krc.co.ke', 'depot_code' => 'NRO', 'role_code' => 'station_officer'],
            ['username' => 'stn_eld', 'name' => 'Eldoret Station Officer', 'email' => 'stationmastereld@krc.co.ke', 'depot_code' => 'ELD', 'role_code' => 'station_officer'],
            ['username' => 'stn_ksm', 'name' => 'Kisumu Station Officer', 'email' => 'stationmasterksm@krc.co.ke', 'depot_code' => 'KSM', 'role_code' => 'station_officer'],
            ['username' => 'stn_mlb', 'name' => 'Malaba Station Officer', 'email' => 'stationmastermlb@krc.co.ke', 'depot_code' => 'MLB', 'role_code' => 'station_officer'],
            ['username' => 'stn_nuk', 'name' => 'Nanyuki Station Officer', 'email' => 'stationmasternuk@krc.co.ke', 'depot_code' => 'NUK', 'role_code' => 'station_officer'],
        ];

        foreach ($users as $u) {
            $password = $defaultPassword ?: strtolower($u['depot_code']) . 'shred';

            DB::table('admin_meta')->updateOrInsert(
                ['collection' => 'users', 'record_id' => $u['username']],
                [
                    'payload' => json_encode([
                        'username' => $u['username'],
                        'name' => $u['name'],
                        'depot' => $u['depot_code'],
                        'role' => $u['role_code'],
                        'pw' => Hash::make($password),
                        'isHQ' => false,
                        'isSuperAdmin' => false,
                    ]),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $password = $defaultPassword ?: strtolower($u['depot_code']) . 'shred';

            if (Schema::hasTable('users')) {
                DB::table('users')->updateOrInsert(
                    ['username' => $u['username']],
                    [
                        'name' => $u['name'],
                        'email' => $u['email'],
                        'depot_code' => $u['depot_code'],
                        'role_code' => $u['role_code'],
                        'permissions' => json_encode([]),
                        'pw' => Hash::make($password),
                        'is_hq' => false,
                        'is_super_admin' => false,
                        'is_active' => true,
                        'metadata' => json_encode([]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
