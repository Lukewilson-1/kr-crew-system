<?php

namespace App\Http\Controllers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class AdminMetaController extends Controller
{
    protected array $collections = ['depotMeta', 'designationMeta', 'statusMeta', 'reportMeta', 'trainTypeMeta', 'shiftMeta', 'users'];
    protected array $directCollections = ['depotMeta', 'statusMeta', 'trainTypeMeta', 'shiftMeta', 'users', 'roles', 'permissions'];

    protected array $normalizedCollections = [
        'depotMeta' => 'depots',
        'statusMeta' => 'status_codes',
        'trainTypeMeta' => 'train_types',
        'shiftMeta' => 'shift_templates',
        'users' => 'users',
        'roles' => 'roles',
        'permissions' => 'permissions',
    ];

    protected function ensureSuperAdminUser(): void
    {
        $config = Config::get('services.superadmin', []);
        $username = trim((string) ($config['username'] ?? 'superadmin'));
        $password = (string) ($config['password'] ?? 'superadmin123');

        if ($username === '') {
            return;
        }

        DB::table('admin_meta')->updateOrInsert(
            ['collection' => 'users', 'record_id' => $username],
            [
                'payload' => json_encode([
                    'username' => $username,
                    'name' => 'Super Admin',
                    'depot' => 'HQ',
                    'role' => 'super_admin',
                    'pw' => $password,
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
                    'pw' => $password,
                    'is_hq' => true,
                    'is_super_admin' => true,
                    'is_active' => true,
                    'metadata' => json_encode([]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    protected function ensureTable(): void
    {
        if (Schema::hasTable('admin_meta')) {
            return;
        }

        Schema::create('admin_meta', function (Blueprint $table) {
            $table->string('collection');
            $table->string('record_id');
            $table->json('payload');
            $table->timestamps();
            $table->primary(['collection', 'record_id']);
        });
    }

    protected function mapNormalizedCollectionRecord(string $collection, object $row): array
    {
        return match ($collection) {
            'depotMeta' => [
                'id' => $row->depot_code,
                'label' => $row->depot_name,
                'region' => $row->region,
                'isHQ' => (bool) $row->is_hq,
                'active' => is_null($row->deleted_at),
                'order' => data_get(json_decode($row->metadata ?? '{}', true), 'order', 999),
                'color' => data_get(json_decode($row->metadata ?? '{}', true), 'color'),
                'restHours' => data_get(json_decode($row->metadata ?? '{}', true), 'restHours'),
            ],
            'statusMeta' => [
                'id' => $row->status_code,
                'label' => $row->status_label,
                'order' => (int) $row->sort_order,
                'active' => is_null($row->deleted_at),
                'bg' => data_get(json_decode($row->metadata ?? '{}', true), 'bg'),
                'fg' => data_get(json_decode($row->metadata ?? '{}', true), 'fg'),
            ],
            'trainTypeMeta' => [
                'id' => $row->train_type_code,
                'label' => $row->train_type_name,
                'order' => (int) $row->sort_order,
                'active' => (bool) $row->is_active,
            ],
            'shiftMeta' => [
                'id' => $row->shift_code,
                'label' => $row->shift_name,
                'order' => (int) $row->sort_order,
                'active' => (bool) $row->is_active,
                'startsAt' => $row->starts_at,
                'endsAt' => $row->ends_at,
            ],
            'users' => [
                'username' => $row->username,
                'id' => $row->username,
                'name' => $row->name,
                'depot' => $row->depot_code,
                'role' => $row->role_code,
                'permissions' => json_decode($row->permissions ?? '[]', true) ?: [],
                'pw' => $row->pw,
                'isHQ' => (bool) $row->is_hq,
                'isSuperAdmin' => (bool) $row->is_super_admin,
                'is_active' => (bool) $row->is_active,
            ],
            'roles' => [
                'id' => $row->role_code,
                'label' => $row->role_name,
                'description' => $row->description,
                'system' => (bool) $row->is_system,
                'active' => is_null($row->deleted_at),
            ],
            'permissions' => [
                'id' => $row->permission_code,
                'label' => $row->permission_name,
                'description' => $row->description,
                'system' => (bool) $row->is_system,
                'active' => is_null($row->deleted_at),
            ],
            default => [
                'id' => $row->record_id,
            ],
        };
    }

    protected function loadNormalizedCollection(string $collection): ?array
    {
        $table = $this->normalizedCollections[$collection] ?? null;
        if ($table === null || !Schema::hasTable($table)) {
            return null;
        }

        $rows = DB::table($table)->get();
        if ($rows->isEmpty()) {
            return null;
        }

        return $rows->map(fn (object $row) => $this->mapNormalizedCollectionRecord($collection, $row))->values()->all();
    }

    protected function persistNormalizedCollection(string $collection, string $id, array $payload): void
    {
        $table = $this->normalizedCollections[$collection] ?? null;
        if ($table === null || !Schema::hasTable($table)) {
            return;
        }

        if ($collection === 'depotMeta') {
            DB::table($table)->updateOrInsert(
                ['depot_code' => $id],
                [
                    'depot_name' => trim((string) ($payload['label'] ?? $id)) ?: $id,
                    'region' => isset($payload['region']) ? trim((string) $payload['region']) : null,
                    'is_hq' => !empty($payload['isHQ']) || !empty($payload['is_hq']),
                    'metadata' => json_encode([
                        'color' => $payload['color'] ?? null,
                        'restHours' => $payload['restHours'] ?? null,
                        'order' => $payload['order'] ?? null,
                        'active' => $payload['active'] ?? true,
                    ]),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            return;
        }

        if ($collection === 'statusMeta') {
            DB::table($table)->updateOrInsert(
                ['status_code' => $id],
                [
                    'status_label' => trim((string) ($payload['label'] ?? $id)) ?: $id,
                    'sort_order' => (int) ($payload['order'] ?? 999),
                    'is_terminal' => !empty($payload['is_terminal']),
                    'metadata' => json_encode([
                        'bg' => $payload['bg'] ?? null,
                        'fg' => $payload['fg'] ?? null,
                        'active' => $payload['active'] ?? true,
                    ]),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            return;
        }

        if ($collection === 'trainTypeMeta') {
            DB::table($table)->updateOrInsert(
                ['train_type_code' => $id],
                [
                    'train_type_name' => trim((string) ($payload['label'] ?? $id)) ?: $id,
                    'sort_order' => (int) ($payload['order'] ?? 999),
                    'is_active' => !array_key_exists('active', $payload) || $payload['active'] !== false,
                    'metadata' => json_encode($payload),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            return;
        }

        if ($collection === 'shiftMeta') {
            DB::table($table)->updateOrInsert(
                ['shift_code' => $id],
                [
                    'shift_name' => trim((string) ($payload['label'] ?? $id)) ?: $id,
                    'starts_at' => !empty($payload['startsAt']) ? $payload['startsAt'] : null,
                    'ends_at' => !empty($payload['endsAt']) ? $payload['endsAt'] : null,
                    'sort_order' => (int) ($payload['order'] ?? 999),
                    'is_active' => !array_key_exists('active', $payload) || $payload['active'] !== false,
                    'metadata' => json_encode($payload),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            return;
        }

        if ($collection === 'users') {
            $permissions = $payload['permissions'] ?? [];
            if (is_string($permissions)) {
                $permissions = array_values(array_filter(array_map('trim', explode(',', $permissions))));
            }
            if (!is_array($permissions)) {
                $permissions = [];
            }

            DB::table($table)->updateOrInsert(
                ['username' => $id],
                [
                    'name' => trim((string) ($payload['name'] ?? $id)) ?: $id,
                    'depot_code' => trim((string) ($payload['depot'] ?? 'HQ')) ?: 'HQ',
                    'role_code' => trim((string) ($payload['role'] ?? 'booking_officer')) ?: 'booking_officer',
                    'permissions' => json_encode(array_values($permissions)),
                    'pw' => (string) ($payload['pw'] ?? 'superadmin123'),
                    'is_hq' => !empty($payload['isHQ']) || !empty($payload['is_hq']),
                    'is_super_admin' => !empty($payload['isSuperAdmin']) || (($payload['role'] ?? '') === 'super_admin'),
                    'is_active' => !array_key_exists('active', $payload) || $payload['active'] !== false,
                    'metadata' => json_encode($payload),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            return;
        }

        if ($collection === 'roles') {
            DB::table($table)->updateOrInsert(
                ['role_code' => $id],
                [
                    'role_name' => trim((string) ($payload['label'] ?? $id)) ?: $id,
                    'description' => isset($payload['description']) ? trim((string) $payload['description']) : null,
                    'is_system' => !empty($payload['system']),
                    'metadata' => json_encode([
                        'active' => $payload['active'] ?? true,
                    ]),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            return;
        }

        if ($collection === 'permissions') {
            DB::table($table)->updateOrInsert(
                ['permission_code' => $id],
                [
                    'permission_name' => trim((string) ($payload['label'] ?? $id)) ?: $id,
                    'description' => isset($payload['description']) ? trim((string) $payload['description']) : null,
                    'is_system' => !empty($payload['system']),
                    'metadata' => json_encode([
                        'active' => $payload['active'] ?? true,
                    ]),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    protected function deleteNormalizedCollection(string $collection, string $id): void
    {
        $table = $this->normalizedCollections[$collection] ?? null;
        if ($table === null || !Schema::hasTable($table)) {
            return;
        }

        $column = match ($collection) {
            'depotMeta' => 'depot_code',
            'statusMeta' => 'status_code',
            'trainTypeMeta' => 'train_type_code',
            'shiftMeta' => 'shift_code',
            'users' => 'username',
            'roles' => 'role_code',
            'permissions' => 'permission_code',
            default => null,
        };

        if ($column !== null) {
            DB::table($table)->where($column, $id)->delete();
        }

        if ($collection === 'users' && Schema::hasTable('admin_meta')) {
            DB::table('admin_meta')->where('collection', 'users')->where('record_id', $id)->delete();
        }
    }

    public function usersIndex()
    {
        $this->ensureSuperAdminUser();
        return $this->normalizedIndex('users');
    }

    public function usersSave(Request $request, string $id)
    {
        return $this->normalizedSave($request, 'users', $id);
    }

    public function usersDelete(string $id)
    {
        return $this->normalizedDelete('users', $id);
    }

    protected function normalizeDirectCollection(string $collection, object $row): array
    {
        return $this->mapNormalizedCollectionRecord($collection, $row);
    }

    public function normalizedIndex(string $collection)
    {
        if (!in_array($collection, $this->directCollections, true)) {
            return response()->json(['error' => 'Invalid collection'], 400);
        }

        $table = $this->normalizedCollections[$collection] ?? null;
        if ($table === null || !Schema::hasTable($table)) {
            return response()->json([]);
        }

        $records = DB::table($table)->get()->map(fn (object $row) => $this->normalizeDirectCollection($collection, $row))->values();

        return response()->json($records);
    }

    public function normalizedSave(Request $request, string $collection, string $id)
    {
        if (!in_array($collection, $this->directCollections, true)) {
            return response()->json(['error' => 'Invalid collection'], 400);
        }

        $payload = $request->json()->all();
        if (!is_array($payload)) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $this->persistNormalizedCollection($collection, $id, $payload);

        return response()->json(['ok' => true, 'record' => array_merge(['id' => $id], $payload)]);
    }

    public function normalizedDelete(string $collection, string $id)
    {
        if (!in_array($collection, $this->directCollections, true)) {
            return response()->json(['error' => 'Invalid collection'], 400);
        }

        $this->deleteNormalizedCollection($collection, $id);

        return response()->json(['ok' => true]);
    }

    public function index(Request $request)
    {
        $this->ensureTable();
        $this->ensureSuperAdminUser();

        $items = DB::table('admin_meta')->get();
        $result = [];

        foreach ($items as $item) {
            $payload = json_decode($item->payload, true) ?: [];
            if (!isset($result[$item->collection])) {
                $result[$item->collection] = [];
            }
            $record = array_merge(['id' => $item->record_id], $payload);
            if ($item->collection === 'users' && !isset($record['username'])) {
                $record['username'] = $item->record_id;
            }
            $result[$item->collection][] = $record;
        }

        foreach (array_keys($this->normalizedCollections) as $collection) {
            $normalizedRecords = $this->loadNormalizedCollection($collection);
            if ($normalizedRecords !== null) {
                $result[$collection] = $normalizedRecords;
            }
        }

        return response()->json($result);
    }

    public function save(Request $request, string $collection, string $id)
    {
        if (!in_array($collection, $this->collections, true)) {
            return response()->json(['error' => 'Invalid collection'], 400);
        }

        $this->ensureTable();

        $payload = $request->json()->all();
        if (!is_array($payload)) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        if ($collection === 'users') {
            $payload['username'] = $id;
        }

        $record = ['collection' => $collection, 'record_id' => $id];
        $exists = DB::table('admin_meta')
            ->where('collection', $collection)
            ->where('record_id', $id)
            ->exists();

        if ($exists) {
            DB::table('admin_meta')
                ->where('collection', $collection)
                ->where('record_id', $id)
                ->update([
                    'payload' => json_encode($payload),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('admin_meta')->insert([
                'collection' => $collection,
                'record_id' => $id,
                'payload' => json_encode($payload),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->persistNormalizedCollection($collection, $id, $payload);

        return response()->json(['ok' => true, 'record' => array_merge(['id' => $id], $payload)]);
    }

    public function delete(string $collection, string $id)
    {
        if (!in_array($collection, $this->collections, true)) {
            return response()->json(['error' => 'Invalid collection'], 400);
        }

        $this->ensureTable();

        DB::table('admin_meta')
            ->where('collection', $collection)
            ->where('record_id', $id)
            ->delete();

        $this->deleteNormalizedCollection($collection, $id);

        if ($collection === 'users' && Schema::hasTable('users')) {
            DB::table('users')->where('username', $id)->delete();
        }

        return response()->json(['ok' => true]);
    }
}
