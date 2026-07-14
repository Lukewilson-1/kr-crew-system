<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SeedSuperAdmin extends Command
{
    protected $signature = 'crew:seed-superadmin';

    protected $description = 'Seed the configured superadmin account into admin_meta.';

    public function handle(): int
    {
        if (!Schema::hasTable('admin_meta')) {
            Schema::create('admin_meta', function (Blueprint $table) {
                $table->string('collection');
                $table->string('record_id');
                $table->json('payload');
                $table->timestamps();
                $table->primary(['collection', 'record_id']);
            });
        }

        $username = trim((string) env('SUPERADMIN_USERNAME', 'superadmin'));
        $password = (string) env('SUPERADMIN_PASSWORD', 'superadmin123');

        if ($username === '') {
            $this->error('SUPERADMIN_USERNAME is empty.');
            return self::FAILURE;
        }

        DB::table('admin_meta')->updateOrInsert(
            ['collection' => 'users', 'record_id' => $username],
            [
                'payload' => json_encode([
                    'username' => $username,
                    'name' => 'Super Admin',
                    'depot' => 'HQ',
                    'role' => 'super_admin',
                    'pw' => Hash::make($password),
                    'isHQ' => true,
                    'isSuperAdmin' => true,
                ]),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        if (Schema::hasTable('users')) {
            DB::table('users')->updateOrInsert(
                ['username' => $username],
                [
                    'name' => 'Super Admin',
                    'depot_code' => 'HQ',
                    'role_code' => 'super_admin',
                    'permissions' => json_encode(['manage_depots', 'manage_users', 'manage_crew', 'manage_roles', 'manage_rosters', 'manage_reports']),
                    'pw' => Hash::make($password),
                    'is_hq' => true,
                    'is_super_admin' => true,
                    'is_active' => true,
                    'metadata' => json_encode([]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->info('Superadmin seeded successfully.');

        return self::SUCCESS;
    }
}