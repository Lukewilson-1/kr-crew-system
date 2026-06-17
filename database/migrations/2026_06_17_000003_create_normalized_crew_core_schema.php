<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

        if (!Schema::hasTable('depots')) {
            Schema::create('depots', function (Blueprint $table) {
                $table->string('depot_code')->primary();
                $table->string('depot_name');
                $table->string('region')->nullable()->index();
                $table->boolean('is_hq')->default(false)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('status_codes')) {
            Schema::create('status_codes', function (Blueprint $table) {
                $table->string('status_code')->primary();
                $table->string('status_label');
                $table->unsignedSmallInteger('sort_order')->default(0)->index();
                $table->boolean('is_terminal')->default(false)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('status_codes')) {
            $defaults = [
                ['status_code' => 'BK', 'status_label' => 'Booked', 'sort_order' => 100, 'is_terminal' => false, 'metadata' => json_encode(['bg' => '#E8F5E9', 'fg' => '#1B5E20', 'active' => true])],
                ['status_code' => 'SB', 'status_label' => 'Stand By', 'sort_order' => 200, 'is_terminal' => false, 'metadata' => json_encode(['bg' => '#E3F2FD', 'fg' => '#0D47A1', 'active' => true])],
                ['status_code' => 'R', 'status_label' => 'Resting', 'sort_order' => 300, 'is_terminal' => false, 'metadata' => json_encode(['bg' => '#F3F5FF', 'fg' => '#4A148C', 'active' => true])],
                ['status_code' => 'L', 'status_label' => 'Leave', 'sort_order' => 400, 'is_terminal' => false, 'metadata' => json_encode(['bg' => '#FFF3E0', 'fg' => '#E65100', 'active' => true])],
                ['status_code' => 'SK', 'status_label' => 'Sick', 'sort_order' => 500, 'is_terminal' => false, 'metadata' => json_encode(['bg' => '#FFEBEE', 'fg' => '#B71C1C', 'active' => true])],
                ['status_code' => 'T', 'status_label' => 'Training', 'sort_order' => 600, 'is_terminal' => false, 'metadata' => json_encode(['bg' => '#E0F2F1', 'fg' => '#00695C', 'active' => true])],
                ['status_code' => 'NTB', 'status_label' => 'NTB', 'sort_order' => 700, 'is_terminal' => false, 'metadata' => json_encode(['bg' => '#ECEFF1', 'fg' => '#37474F', 'active' => true])],
                ['status_code' => 'TO', 'status_label' => 'Trip Off', 'sort_order' => 800, 'is_terminal' => false, 'metadata' => json_encode(['bg' => '#FCE4EC', 'fg' => '#AD1457', 'active' => true])],
            ];

            foreach ($defaults as $default) {
                DB::table('status_codes')->updateOrInsert(
                    ['status_code' => $default['status_code']],
                    [
                        'status_label' => $default['status_label'],
                        'sort_order' => $default['sort_order'],
                        'is_terminal' => $default['is_terminal'],
                        'metadata' => $default['metadata'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        if (!Schema::hasTable('shift_templates')) {
            Schema::create('shift_templates', function (Blueprint $table) {
                $table->string('shift_code')->primary();
                $table->string('shift_name');
                $table->time('starts_at')->nullable();
                $table->time('ends_at')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('train_types')) {
            Schema::create('train_types', function (Blueprint $table) {
                $table->string('train_type_code')->primary();
                $table->string('train_type_name');
                $table->unsignedSmallInteger('sort_order')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('routes')) {
            Schema::create('routes', function (Blueprint $table) {
                $table->string('route_code')->primary();
                $table->string('route_name');
                $table->string('origin_depot_code')->nullable()->index();
                $table->string('destination_depot_code')->nullable()->index();
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('rest_locations')) {
            Schema::create('rest_locations', function (Blueprint $table) {
                $table->string('rest_location_code')->primary();
                $table->string('rest_location_name');
                $table->string('depot_code')->nullable()->index();
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->string('role_code')->primary();
                $table->string('role_name');
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false)->index();
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
                $table->boolean('is_system')->default(false)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->string('role_code')->index();
                $table->string('permission_code')->index();
                $table->timestamps();
                $table->primary(['role_code', 'permission_code']);
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->string('username')->primary();
                $table->string('name');
                $table->string('depot_code')->nullable()->index();
                $table->string('role_code')->nullable()->index();
                $table->json('permissions')->nullable();
                $table->string('pw');
                $table->boolean('is_hq')->default(false)->index();
                $table->boolean('is_super_admin')->default(false)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('user_roles')) {
            Schema::create('user_roles', function (Blueprint $table) {
                $table->string('username')->index();
                $table->string('role_code')->index();
                $table->timestamps();
                $table->primary(['username', 'role_code']);
            });
        }

        if (!Schema::hasTable('user_permissions')) {
            Schema::create('user_permissions', function (Blueprint $table) {
                $table->string('username')->index();
                $table->string('permission_code')->index();
                $table->timestamps();
                $table->primary(['username', 'permission_code']);
            });
        }

        if (!Schema::hasTable('crew_members')) {
            Schema::create('crew_members', function (Blueprint $table) {
                $table->string('record_id')->primary();
                $table->string('crew_id')->nullable()->unique();
                $table->string('staff_number')->nullable()->unique();
                $table->string('depot_code')->nullable()->index();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('display_name')->nullable()->index();
                $table->string('designation_code')->nullable()->index();
                $table->string('employment_status_code')->nullable()->index();
                $table->date('hire_date')->nullable()->index();
                $table->string('phone')->nullable();
                $table->string('email')->nullable()->index();
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('crew_status_history')) {
            Schema::create('crew_status_history', function (Blueprint $table) {
                $table->string('history_id')->primary();
                $table->string('crew_record_id')->index();
                $table->string('crew_id')->nullable()->index();
                $table->string('depot_code')->nullable()->index();
                $table->string('status_code')->index();
                $table->string('reason_code')->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamp('effective_at')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('duty_rosters')) {
            Schema::create('duty_rosters', function (Blueprint $table) {
                $table->string('roster_id')->primary();
                $table->string('depot_code')->index();
                $table->date('roster_date')->index();
                $table->string('period_label')->nullable()->index();
                $table->string('status')->default('draft')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('duty_roster_items')) {
            Schema::create('duty_roster_items', function (Blueprint $table) {
                $table->string('item_id')->primary();
                $table->string('roster_id')->index();
                $table->string('crew_record_id')->index();
                $table->string('crew_id')->nullable()->index();
                $table->string('shift_code')->nullable()->index();
                $table->string('train_type_code')->nullable()->index();
                $table->string('route_code')->nullable()->index();
                $table->string('rest_location_code')->nullable()->index();
                $table->date('duty_date')->index();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('crew_records') && Schema::hasTable('crew_members')) {
            $rows = DB::table('crew_records')->get();
            foreach ($rows as $row) {
                $payload = json_decode($row->payload ?? '{}', true);
                if (!is_array($payload)) {
                    $payload = [];
                }

                $displayName = trim((string) ($payload['display_name'] ?? $payload['displayName'] ?? $payload['name'] ?? ''));
                if ($displayName === '') {
                    $displayName = trim((string) ($payload['first_name'] ?? '') . ' ' . (string) ($payload['last_name'] ?? ''));
                }

                DB::table('crew_members')->updateOrInsert(
                    ['record_id' => $row->record_id],
                    [
                        'crew_id' => trim((string) ($payload['id'] ?? $row->crew_id ?? $row->record_id)) ?: $row->record_id,
                        'staff_number' => trim((string) ($payload['staff_number'] ?? $row->staff_number ?? '')) ?: null,
                        'depot_code' => trim((string) ($payload['depot'] ?? $row->depot ?? 'HQ')) ?: 'HQ',
                        'first_name' => trim((string) ($payload['first_name'] ?? '')) ?: null,
                        'last_name' => trim((string) ($payload['last_name'] ?? '')) ?: null,
                        'display_name' => $displayName !== '' ? $displayName : null,
                        'designation_code' => trim((string) ($payload['grade'] ?? $payload['designation_code'] ?? $payload['designation'] ?? '')) ?: null,
                        'employment_status_code' => trim((string) ($payload['status'] ?? $payload['employment_status_code'] ?? '')) ?: null,
                        'hire_date' => trim((string) ($payload['hire_date'] ?? $payload['hireDate'] ?? '')) ?: null,
                        'phone' => trim((string) ($payload['phone'] ?? '')) ?: null,
                        'email' => trim((string) ($payload['email'] ?? '')) ?: null,
                        'is_active' => !array_key_exists('active', $payload) || $payload['active'] !== false,
                        'metadata' => json_encode($payload),
                        'created_at' => $row->created_at ?: now(),
                        'updated_at' => $row->updated_at ?: now(),
                    ]
                );

                $status = trim((string) ($payload['status'] ?? ''));
                if ($status !== '') {
                    $historyExists = DB::table('crew_status_history')
                        ->where('crew_record_id', $row->record_id)
                        ->exists();

                    if (!$historyExists) {
                        DB::table('crew_status_history')->insert([
                            'history_id' => $row->record_id . '_legacy',
                            'crew_record_id' => $row->record_id,
                            'crew_id' => trim((string) ($payload['id'] ?? $row->crew_id ?? $row->record_id)) ?: $row->record_id,
                            'depot_code' => trim((string) ($payload['depot'] ?? $row->depot ?? 'HQ')) ?: 'HQ',
                            'status_code' => $status,
                            'reason_code' => null,
                            'notes' => trim((string) ($payload['notes'] ?? '')) ?: null,
                            'effective_at' => $row->updated_at ?: now(),
                            'metadata' => json_encode([
                                'shift' => $payload['shift'] ?? null,
                                'trainType' => $payload['trainType'] ?? null,
                                'route' => $payload['route'] ?? null,
                            ]),
                            'created_at' => $row->created_at ?: now(),
                            'updated_at' => $row->updated_at ?: now(),
                        ]);
                    }
                }
            }
        }

        if (Schema::hasTable('admin_meta') && Schema::hasTable('users')) {
            $items = DB::table('admin_meta')->where('collection', 'users')->get();
            foreach ($items as $item) {
                $payload = json_decode($item->payload ?? '{}', true);
                if (!is_array($payload)) {
                    $payload = [];
                }

                $username = trim((string) ($payload['username'] ?? $item->record_id));
                if ($username === '') {
                    continue;
                }

                $permissions = $payload['permissions'] ?? [];
                if (is_string($permissions)) {
                    $permissions = array_values(array_filter(array_map('trim', explode(',', $permissions))));
                }
                if (!is_array($permissions)) {
                    $permissions = [];
                }

                DB::table('users')->updateOrInsert(
                    ['username' => $username],
                    [
                        'name' => trim((string) ($payload['name'] ?? $username)) ?: $username,
                        'depot_code' => trim((string) ($payload['depot'] ?? 'HQ')) ?: 'HQ',
                        'role_code' => trim((string) ($payload['role'] ?? 'booking_officer')) ?: 'booking_officer',
                        'permissions' => json_encode(array_values($permissions)),
                        'pw' => (string) ($payload['pw'] ?? 'superadmin123'),
                        'is_hq' => !empty($payload['isHQ']),
                        'is_super_admin' => !empty($payload['isSuperAdmin']) || (($payload['role'] ?? '') === 'super_admin'),
                        'is_active' => $payload['is_active'] ?? true,
                        'metadata' => json_encode($payload),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
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
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_roster_items');
        Schema::dropIfExists('duty_rosters');
        Schema::dropIfExists('crew_status_history');
        Schema::dropIfExists('crew_members');
        Schema::dropIfExists('rest_locations');
        Schema::dropIfExists('routes');
        Schema::dropIfExists('train_types');
        Schema::dropIfExists('shift_templates');
        Schema::dropIfExists('status_codes');
        Schema::dropIfExists('depots');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};