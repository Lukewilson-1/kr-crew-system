<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->string('role_code')->primary();
                $table->string('role_name');
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->string('permission_code')->primary();
                $table->string('permission_name');
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->string('role_code');
                $table->string('permission_code');
                $table->timestamps();
                $table->primary(['role_code', 'permission_code']);
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->string('username')->primary();
                $table->string('name');
                $table->string('email')->nullable()->unique();
                $table->string('depot_code')->nullable()->index();
                $table->string('role_code')->nullable()->index();
                $table->json('permissions')->nullable();
                $table->string('password')->nullable();
                $table->string('pw')->nullable();
                $table->boolean('is_hq')->default(false)->index();
                $table->boolean('is_super_admin')->default(false)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            if (!Schema::hasColumn('users', 'email')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('email')->nullable()->unique()->after('name');
                });
            }

            if (!Schema::hasColumn('users', 'password')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('password')->nullable()->after('pw');
                });
            }

            if (!Schema::hasColumn('users', 'remember_token')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->rememberToken();
                });
            }
        }

        if (!Schema::hasTable('user_roles')) {
            Schema::create('user_roles', function (Blueprint $table) {
                $table->string('username');
                $table->string('role_code');
                $table->timestamps();
                $table->primary(['username', 'role_code']);
            });
        }

        if (!Schema::hasTable('user_permissions')) {
            Schema::create('user_permissions', function (Blueprint $table) {
                $table->string('username');
                $table->string('permission_code');
                $table->timestamps();
                $table->primary(['username', 'permission_code']);
            });
        }

        if (Schema::hasTable('roles')) {
            $seedRoles = [
                'super_admin' => ['Super Admin', 'Full system control'],
                'hq_admin' => ['HQ Admin', 'Headquarters operations'],
                'station_officer' => ['Station Officer', 'Station and depot operations'],
                'booking_officer' => ['Booking Officer', 'Crew booking and registers'],
                'crew_admin' => ['Crew Admin', 'Crew maintenance and lookup data'],
            ];

            foreach ($seedRoles as $code => [$name, $description]) {
                DB::table('roles')->updateOrInsert(
                    ['role_code' => $code],
                    [
                        'role_name' => $name,
                        'description' => $description,
                        'is_system' => true,
                        'metadata' => json_encode([]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        if (Schema::hasTable('permissions')) {
            $seedPermissions = [
                'manage_depots' => 'Create and edit depots',
                'manage_users' => 'Create and edit users',
                'manage_crew' => 'Create and edit crew members',
                'manage_roles' => 'Assign roles and permissions',
                'manage_rosters' => 'Maintain roster data',
                'manage_reports' => 'Configure report visibility',
            ];

            foreach ($seedPermissions as $code => $name) {
                DB::table('permissions')->updateOrInsert(
                    ['permission_code' => $code],
                    [
                        'permission_name' => $name,
                        'description' => null,
                        'is_system' => true,
                        'metadata' => json_encode([]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $superuser = DB::table('users')->where('username', 'superadmin')->first();
        if (! $superuser) {
            DB::table('users')->insert([
                'username' => 'superadmin',
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'depot_code' => 'HQ',
                'role_code' => 'super_admin',
                'permissions' => json_encode(['manage_depots', 'manage_users', 'manage_crew', 'manage_roles', 'manage_rosters', 'manage_reports']),
                'password' => Hash::make('superadmin123'),
                'pw' => Hash::make('superadmin123'),
                'is_hq' => true,
                'is_super_admin' => true,
                'is_active' => true,
                'metadata' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('users')->where('username', 'superadmin')->update([
                'email' => 'superadmin@example.com',
                'depot_code' => 'HQ',
                'role_code' => 'super_admin',
                'permissions' => json_encode(['manage_depots', 'manage_users', 'manage_crew', 'manage_roles', 'manage_rosters', 'manage_reports']),
                'password' => !empty($superuser->password) ? $superuser->password : Hash::make('superadmin123'),
                'pw' => !empty($superuser->pw) ? $superuser->pw : Hash::make('superadmin123'),
                'is_hq' => true,
                'is_super_admin' => true,
                'is_active' => true,
                'metadata' => json_encode([]),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password', 'remember_token']);
        });
    }
};
