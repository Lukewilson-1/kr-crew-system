<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

class CrewDataController extends Controller
{
    protected string $table = 'crew_records';
    protected string $shiftTable = 'crew_shift_assignments';
    protected string $memberTable = 'crew_members';
    protected string $historyTable = 'crew_status_history';
    protected string $segmentTable = 'crew_status_segments';

    protected function hasGlobalCrewAccess(Request $request): bool
    {
        $user = $request->user();
        return (bool) ($user?->is_hq) || (bool) ($user?->is_super_admin)
            || in_array($user?->role_code, ['hq_admin', 'super_admin'], true)
            || $user?->depot_code === 'HQ';
    }

    protected function depotMatches(string $left, string $right): bool
    {
        if (strcasecmp(trim($left), trim($right)) === 0) {
            return true;
        }

        if (!Schema::hasTable('depots')) {
            return false;
        }

        $values = [strtolower(trim($left)), strtolower(trim($right))];
        $depot = DB::table('depots')
            ->where(function ($query) use ($values) {
                $query->whereIn(DB::raw('LOWER(depot_code)'), $values)
                    ->orWhereIn(DB::raw('LOWER(depot_name)'), $values);
            })
            ->first();

        return $depot && in_array(strtolower(trim($left)), [strtolower($depot->depot_code), strtolower($depot->depot_name)], true)
            && in_array(strtolower(trim($right)), [strtolower($depot->depot_code), strtolower($depot->depot_name)], true);
    }

    protected function authorizeCrewDepot(Request $request, string $depot): void
    {
        if ($this->hasGlobalCrewAccess($request)) {
            return;
        }

        $userDepot = (string) ($request->user()?->depot_code ?? '');
        abort_unless($userDepot !== '' && $this->depotMatches($userDepot, $depot), 403, 'You may only manage crew at your depot.');
    }

    protected function payloadFromRow(object $row): array
    {
        $payload = json_decode($row->payload ?? '{}', true);
        return is_array($payload) ? $payload : [];
    }

    protected function payloadValue(array $payload, string $key, mixed $fallback = null): mixed
    {
        $value = $payload[$key] ?? null;
        return $value === null || $value === '' ? $fallback : $value;
    }

    protected function buildCrewViewRecord(object $row, ?object $member = null, ?object $assignment = null, ?object $latestHistory = null, ?array $segments = null): array
    {
        $payload = $this->payloadFromRow($row);
        $member ??= DB::table($this->memberTable)->where('record_id', $row->record_id)->first();
        $assignment ??= DB::table($this->shiftTable)->where('crew_record_id', $row->record_id)->first();
        $latestHistory ??= DB::table($this->historyTable)->where('crew_record_id', $row->record_id)->orderByDesc('effective_at')->orderByDesc('created_at')->first();
        $monthKey = $payload['monthKey'] ?? null;
        $segments ??= $this->fetchStatusSegments($row->record_id, $monthKey);
        $computedMonthly = !empty($segments) ? $this->buildDailyMonthlyFromSegments($segments) : [];
        $monthly = !empty($computedMonthly) ? $computedMonthly : ($payload['monthly'] ?? []);
        $finalStatus = $this->payloadValue($payload, 'status', $latestHistory?->status_code ?? '');
        if ($finalStatus === '' && !empty($segments)) {
            $finalStatus = $this->computeFinalStatusFromSegments($segments, (int) date('j')) ?? '';
        }
        if ($finalStatus === '' && !empty($segments)) {
            $finalStatus = $this->computeFinalStatusFromSegments($segments) ?? '';
        }

        return array_merge($payload, [
            'id' => $this->payloadValue($payload, 'id', $row->crew_id ?: $row->record_id),
            'record_id' => $row->record_id,
            'name' => $this->payloadValue($payload, 'name', $member?->display_name ?: trim((string) ($member?->first_name ?? '') . ' ' . (string) ($member?->last_name ?? '')) ?: $row->record_id),
            'grade' => $this->payloadValue($payload, 'grade', $member?->designation_code ?? ''),
            'depot' => $this->payloadValue($payload, 'depot', $member?->depot_code ?? $row->depot),
            'staff_number' => $this->payloadValue($payload, 'staff_number', $member?->staff_number ?? $row->staff_number ?? ''),
            'shift' => $this->payloadValue($payload, 'shift', $assignment?->shift ?? ''),
            'status' => $finalStatus,
            'route' => $payload['route'] ?? '',
            'trainType' => $payload['trainType'] ?? '',
            'bookTime' => $payload['bookTime'] ?? '',
            'notes' => $payload['notes'] ?? '',
            'since' => $payload['since'] ?? '',
            'restStarted' => $payload['restStarted'] ?? null,
            'awayDepot' => $payload['awayDepot'] ?? null,
            'monthly' => $monthly,
            'monthKey' => $payload['monthKey'] ?? null,
            'status_segments' => $segments,
            'final_status' => $finalStatus,
            'lastUpdated' => $payload['lastUpdated'] ?? $row->updated_at,
        ]);
    }

    protected function loadCrewViewRows(?string $depot = null, ?string $monthKey = null): array
    {
        $query = DB::table($this->table);
        if ($depot !== null && $depot !== '') {
            // Existing crew records may contain a depot name ("Changamwe")
            // while users and metadata use its code ("CGW"). Accept either
            // form so station users see the same records as HQ.
            $depots = [$depot];
            if (Schema::hasTable('depots')) {
                $depotRow = DB::table('depots')
                    ->whereRaw('LOWER(depot_code) = ?', [strtolower($depot)])
                    ->orWhereRaw('LOWER(depot_name) = ?', [strtolower($depot)])
                    ->first();
                if ($depotRow) {
                    $depots[] = $depotRow->depot_code;
                    $depots[] = $depotRow->depot_name;
                }
            }
            $query->whereIn('depot', array_values(array_unique($depots)));
        }

        $rows = $query->orderBy('depot')->orderBy('crew_id')->get();
        $recordIds = $rows->pluck('record_id')->all();
        if (empty($recordIds)) {
            return [];
        }

        // Fetch related data in bulk. The prior implementation issued four extra
        // queries per crew member, making each navigation increasingly slow.
        $members = DB::table($this->memberTable)->whereIn('record_id', $recordIds)->get()->keyBy('record_id');
        $assignments = DB::table($this->shiftTable)->whereIn('crew_record_id', $recordIds)->get()->keyBy('crew_record_id');
        $histories = DB::table($this->historyTable)->whereIn('crew_record_id', $recordIds)
            ->orderByDesc('effective_at')->orderByDesc('created_at')->get()
            ->unique('crew_record_id')->keyBy('crew_record_id');
        $segmentRows = DB::table($this->segmentTable)->whereIn('crew_record_id', $recordIds)
            ->when($monthKey, fn ($query) => $query->where('month_key', $monthKey))
            ->orderBy('day')->orderBy('sort_order')->orderBy('created_at')->get();
        $segmentsByRecord = $segmentRows->groupBy('crew_record_id')->map(fn ($segments) => $segments->map(function ($row) {
            return [
                'segment_id' => $row->segment_id, 'crew_record_id' => $row->crew_record_id,
                'crew_id' => $row->crew_id, 'depot_code' => $row->depot_code, 'month_key' => $row->month_key,
                'day' => (int) $row->day, 'sort_order' => (int) $row->sort_order, 'status_code' => $row->status_code,
                'train_type' => $row->train_type, 'route' => $row->route, 'book_time' => $row->book_time,
                'rest_started_at' => $row->rest_started_at, 'away_depot' => $row->away_depot, 'notes' => $row->notes,
                'metadata' => json_decode($row->metadata ?? 'null', true), 'created_at' => $row->created_at, 'updated_at' => $row->updated_at,
            ];
        })->all());
        $records = [];

        foreach ($rows as $row) {
            $recordSegments = $segmentsByRecord->get($row->record_id, []);
            if ($monthKey === null || $monthKey === '') {
                $recordMonthKey = (string) ($this->payloadFromRow($row)['monthKey'] ?? '');
                if ($recordMonthKey !== '') {
                    $recordSegments = array_values(array_filter($recordSegments, fn (array $segment) => ($segment['month_key'] ?? '') === $recordMonthKey));
                }
            }
            $record = $this->buildCrewViewRecord($row, $members->get($row->record_id), $assignments->get($row->record_id), $histories->get($row->record_id), $recordSegments);
            if ($monthKey !== null && $monthKey !== '') {
                $recordMonthKey = (string) ($record['monthKey'] ?? '');
                if ($recordMonthKey !== $monthKey) {
                    continue;
                }
            }

            $records[] = $record;
        }

        return $records;
    }

    protected function loadCrewViewRecord(string $recordId): ?array
    {
        $row = DB::table($this->table)->where('record_id', $this->normalizeRecordId($recordId))->first();
        return $row ? $this->buildCrewViewRecord($row) : null;
    }

    protected function ensureTables(): void
    {
        if (!Schema::hasTable($this->table)) {
            Schema::create($this->table, function (Blueprint $table) {
                $table->string('record_id')->primary();
                $table->string('depot')->index();
                $table->string('crew_id')->nullable()->index();
                $table->string('staff_number')->nullable()->index();
                $table->longText('payload');
                $table->timestamps();
            });
        } else {
            if (!Schema::hasColumn($this->table, 'staff_number')) {
                Schema::table($this->table, function (Blueprint $table) {
                    $table->string('staff_number')->nullable()->index()->after('crew_id');
                });
            }
        }

        if (!Schema::hasTable($this->shiftTable)) {
            Schema::create($this->shiftTable, function (Blueprint $table) {
                $table->string('crew_record_id')->primary();
                $table->string('record_id')->index();
                $table->string('crew_id')->nullable()->index();
                $table->string('depot')->index();
                $table->string('shift')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable($this->memberTable)) {
            Schema::create($this->memberTable, function (Blueprint $table) {
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

        if (!Schema::hasTable($this->historyTable)) {
            Schema::create($this->historyTable, function (Blueprint $table) {
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

        if (!Schema::hasTable($this->segmentTable)) {
            Schema::create($this->segmentTable, function (Blueprint $table) {
                $table->string('segment_id')->primary();
                $table->string('crew_record_id')->index();
                $table->string('crew_id')->nullable()->index();
                $table->string('depot_code')->nullable()->index();
                $table->string('month_key')->nullable()->index();
                $table->unsignedTinyInteger('day')->index();
                $table->unsignedSmallInteger('sort_order')->default(100)->index();
                $table->string('status_code')->index();
                $table->string('train_type')->nullable();
                $table->string('route')->nullable();
                $table->string('book_time')->nullable();
                $table->timestamp('rest_started_at')->nullable();
                $table->string('away_depot')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            $segmentColumns = [
                'crew_id' => fn (Blueprint $table) => $table->string('crew_id')->nullable()->index(),
                'depot_code' => fn (Blueprint $table) => $table->string('depot_code')->nullable()->index(),
                'month_key' => fn (Blueprint $table) => $table->string('month_key')->nullable()->index(),
                'day' => fn (Blueprint $table) => $table->unsignedTinyInteger('day')->nullable()->index(),
                'sort_order' => fn (Blueprint $table) => $table->unsignedSmallInteger('sort_order')->default(100)->index(),
                'status_code' => fn (Blueprint $table) => $table->string('status_code')->nullable()->index(),
                'train_type' => fn (Blueprint $table) => $table->string('train_type')->nullable(),
                'route' => fn (Blueprint $table) => $table->string('route')->nullable(),
                'book_time' => fn (Blueprint $table) => $table->string('book_time')->nullable(),
                'rest_started_at' => fn (Blueprint $table) => $table->timestamp('rest_started_at')->nullable(),
                'away_depot' => fn (Blueprint $table) => $table->string('away_depot')->nullable(),
                'notes' => fn (Blueprint $table) => $table->text('notes')->nullable(),
                'metadata' => fn (Blueprint $table) => $table->json('metadata')->nullable(),
            ];

            foreach ($segmentColumns as $column => $callback) {
                if (!Schema::hasColumn($this->segmentTable, $column)) {
                    Schema::table($this->segmentTable, $callback);
                }
            }
        }
    }

    protected function normalizeText(mixed $value): string
    {
        return trim((string) $value);
    }

    protected function buildDisplayName(array $payload): string
    {
        $displayName = $this->normalizeText($payload['display_name'] ?? $payload['displayName'] ?? $payload['name'] ?? '');
        if ($displayName !== '') {
            return $displayName;
        }

        $firstName = $this->normalizeText($payload['first_name'] ?? '');
        $lastName = $this->normalizeText($payload['last_name'] ?? '');
        return trim($firstName . ' ' . $lastName);
    }

    protected function syncCrewMember(string $recordId, array $payload, string $depot, ?object $existing = null): void
    {
        $displayName = $this->buildDisplayName($payload);
        $firstName = $this->normalizeText($payload['first_name'] ?? '');
        $lastName = $this->normalizeText($payload['last_name'] ?? '');
        $crewId = $this->normalizeText($payload['id'] ?? $existing?->crew_id ?? $recordId) ?: $recordId;
        $staffNumber = $this->normalizeText($payload['staff_number'] ?? $existing?->staff_number ?? '');

        if ($displayName === '' && ($firstName !== '' || $lastName !== '')) {
            $displayName = trim($firstName . ' ' . $lastName);
        }

        $member = DB::table($this->memberTable)->where('record_id', $recordId)->first();

        if (! $member && $crewId !== '') {
            $member = DB::table($this->memberTable)->where('crew_id', $crewId)->first();
        }

        if (! $member && $staffNumber !== '') {
            $member = DB::table($this->memberTable)->where('staff_number', $staffNumber)->first();
        }

        DB::table($this->memberTable)->updateOrInsert(
            ['record_id' => $member?->record_id ?? $recordId],
            [
                'record_id' => $recordId,
                'crew_id' => $crewId,
                'staff_number' => $staffNumber !== '' ? $staffNumber : null,
                'depot_code' => $this->normalizeText($payload['depot'] ?? $depot) ?: $depot,
                'first_name' => $firstName !== '' ? $firstName : null,
                'last_name' => $lastName !== '' ? $lastName : null,
                'display_name' => $displayName !== '' ? $displayName : null,
                'designation_code' => ($designation = $this->normalizeText($payload['grade'] ?? $payload['designation_code'] ?? $payload['designation'] ?? '')) !== '' ? $designation : null,
                'employment_status_code' => ($status = $this->normalizeText($payload['status'] ?? $payload['employment_status_code'] ?? '')) !== '' ? $status : null,
                'hire_date' => ($hireDate = $this->normalizeText($payload['hire_date'] ?? $payload['hireDate'] ?? '')) !== '' ? $hireDate : null,
                'phone' => ($phone = $this->normalizeText($payload['phone'] ?? '')) !== '' ? $phone : null,
                'email' => ($email = $this->normalizeText($payload['email'] ?? '')) !== '' ? $email : null,
                'is_active' => !array_key_exists('active', $payload) || $payload['active'] !== false,
                'metadata' => json_encode($payload),
                'created_at' => $member?->created_at ?? ($existing?->created_at ?: now()),
                'updated_at' => now(),
            ]
        );
    }

    protected function appendStatusHistory(string $recordId, array $payload, ?object $existing = null): void
    {
        $status = $this->normalizeText($payload['status'] ?? '');
        if ($status === '') {
            return;
        }

        $previousStatus = $this->normalizeText($existing?->payload ? (json_decode($existing->payload, true)['status'] ?? '') : '');
        if ($existing && $previousStatus === $status) {
            return;
        }

        DB::table($this->historyTable)->insert([
            'history_id' => (string) Str::uuid(),
            'crew_record_id' => $recordId,
            'crew_id' => $this->normalizeText($payload['id'] ?? $existing?->crew_id ?? $recordId) ?: $recordId,
            'depot_code' => $this->normalizeText($payload['depot'] ?? $existing?->depot ?? '' ) ?: null,
            'status_code' => $status,
            'reason_code' => $this->normalizeText($payload['reason_code'] ?? $payload['reason'] ?? '') ?: null,
            'notes' => $this->normalizeText($payload['notes'] ?? '') ?: null,
            'effective_at' => now(),
            'metadata' => json_encode([
                'shift' => $payload['shift'] ?? null,
                'trainType' => $payload['trainType'] ?? null,
                'route' => $payload['route'] ?? null,
                'updatedBy' => $payload['updatedBy'] ?? null,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function fetchStatusSegments(string $recordId, ?string $monthKey = null): array
    {
        if (!Schema::hasTable($this->segmentTable)) {
            return [];
        }

        $query = DB::table($this->segmentTable)
            ->where('crew_record_id', $recordId);

        if ($monthKey !== null && $monthKey !== '') {
            $query->where('month_key', $monthKey);
        }

        $rows = $query
            ->orderBy('day')
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        return array_map(function ($row) {
            $toIso8601 = function ($value): ?string {
                if ($value === null || $value === '') {
                    return null;
                }

                if (is_string($value)) {
                    return $value;
                }

                if (method_exists($value, 'toIso8601String')) {
                    return $value->toIso8601String();
                }

                return (string) $value;
            };

            return [
                'segment_id' => $row->segment_id,
                'crew_record_id' => $row->crew_record_id,
                'crew_id' => $row->crew_id,
                'depot_code' => $row->depot_code,
                'month_key' => $row->month_key,
                'day' => (int) $row->day,
                'sort_order' => (int) $row->sort_order,
                'status_code' => $row->status_code,
                'train_type' => $row->train_type,
                'route' => $row->route,
                'book_time' => $row->book_time,
                'rest_started_at' => $toIso8601($row->rest_started_at),
                'away_depot' => $row->away_depot,
                'notes' => $row->notes,
                'metadata' => json_decode($row->metadata ?? 'null', true),
                'created_at' => $toIso8601($row->created_at),
                'updated_at' => $toIso8601($row->updated_at),
            ];
        }, $rows->all());
    }

    protected function buildDailyMonthlyFromSegments(array $segments): array
    {
        $monthly = [];
        usort($segments, function ($left, $right) {
            $leftDay = (int) ($left['day'] ?? 0);
            $rightDay = (int) ($right['day'] ?? 0);
            if ($leftDay === $rightDay) {
                return ((int) ($left['sort_order'] ?? 100)) <=> ((int) ($right['sort_order'] ?? 100));
            }
            return $leftDay <=> $rightDay;
        });

        foreach ($segments as $segment) {
            $day = $segment['day'] ?? null;
            if (!is_int($day) || $day < 1 || $day > 31) {
                continue;
            }
            $monthly['d'.$day] = $segment['status_code'] ?? '';
        }
        return $monthly;
    }

    protected function computeFinalStatusFromSegments(array $segments, ?int $day = null): ?string
    {
        $status = null;
        foreach ($segments as $segment) {
            if ($day !== null && $segment['day'] !== $day) {
                continue;
            }
            $status = $segment['status_code'] ?? $status;
        }
        return $status;
    }

    protected function syncCrewStatusSegments(string $recordId, array $payload, ?object $existing = null): void
    {
        if (!Schema::hasTable($this->segmentTable)) {
            return;
        }

        $monthKey = trim((string) ($payload['monthKey'] ?? ''));
        if ($monthKey === '') {
            return;
        }

        $segments = [];
        if (is_array($payload['status_segments'] ?? null)) {
            foreach ($payload['status_segments'] as $segment) {
                if (!is_array($segment)) {
                    continue;
                }
                $segments[] = $segment;
            }
        } elseif (is_array($payload['monthly'] ?? null)) {
            foreach ($payload['monthly'] as $key => $code) {
                if (!is_string($key) || !preg_match('/^d(\d+)$/', $key, $matches)) {
                    continue;
                }
                $day = (int) $matches[1];
                $segments[] = [
                    'day' => $day,
                    'sort_order' => 100,
                    'status_code' => trim((string) $code),
                    'train_type' => $payload['trainType'] ?? null,
                    'route' => $payload['route'] ?? null,
                    'book_time' => $payload['bookTime'] ?? null,
                    'rest_started_at' => null,
                    'away_depot' => $payload['awayDepot'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                    'metadata' => json_encode([
                        'updatedBy' => $payload['updatedBy'] ?? null,
                    ]),
                ];
            }
        }

        foreach ($segments as $segment) {
            $day = isset($segment['day']) ? (int) $segment['day'] : 0;
            $statusCode = trim((string) ($segment['status_code'] ?? ''));
            if ($day < 1 || $day > 31) {
                continue;
            }

            if ($statusCode === '') {
                DB::table($this->segmentTable)
                    ->where('crew_record_id', $recordId)
                    ->where('month_key', $monthKey)
                    ->where('day', $day)
                    ->delete();
                continue;
            }

            $segmentPayload = [
                'crew_id' => $this->normalizeText($payload['id'] ?? $existing?->crew_id ?? $recordId) ?: $recordId,
                'depot_code' => $this->normalizeText($payload['depot'] ?? $existing?->depot ?? '') ?: null,
                'month_key' => $monthKey,
                'day' => $day,
                'sort_order' => (int) ($segment['sort_order'] ?? 100),
                'status_code' => $statusCode,
                'status' => $statusCode,
                'train_type' => trim((string) ($segment['train_type'] ?? '')) ?: null,
                'route' => trim((string) ($segment['route'] ?? '')) ?: null,
                'book_time' => trim((string) ($segment['book_time'] ?? '')) ?: null,
                'start_time' => trim((string) ($segment['start_time'] ?? $segment['book_time'] ?? '00:00')) ?: '00:00',
                'end_time' => trim((string) ($segment['end_time'] ?? $segment['book_time'] ?? '23:59')) ?: '23:59',
                'rest_started_at' => $segment['rest_started_at'] ?? null,
                'away_depot' => trim((string) ($segment['away_depot'] ?? '')) ?: null,
                'notes' => trim((string) ($segment['notes'] ?? '')) ?: null,
                'note' => trim((string) ($segment['notes'] ?? '')) ?: null,
                'metadata' => $segment['metadata'] ?? json_encode([]),
                'created_at' => $existing?->created_at ?: now(),
                'updated_at' => now(),
            ];

            if (in_array(Schema::getColumnType($this->segmentTable, 'segment_id'), ['string', 'varchar', 'text'], true)) {
                $segmentPayload['segment_id'] = (string) ($segment['segment_id'] ?? Str::uuid());
            }

            if (Schema::hasColumn($this->segmentTable, 'date')) {
                $segmentPayload['date'] = $this->resolveSegmentDate($monthKey, $day);
            }

            if (Schema::hasColumn($this->segmentTable, 'status')) {
                $segmentPayload['status'] = $statusCode;
            }

            if (Schema::hasColumn($this->segmentTable, 'note')) {
                $segmentPayload['note'] = trim((string) ($segment['notes'] ?? '')) ?: null;
            }

            if (Schema::hasColumn($this->segmentTable, 'start_time')) {
                $segmentPayload['start_time'] = trim((string) ($segment['book_time'] ?? '')) ?: '00:00';
            }

            if (Schema::hasColumn($this->segmentTable, 'end_time')) {
                $segmentPayload['end_time'] = trim((string) ($segment['book_time'] ?? '')) ?: '23:59';
            }

            DB::table($this->segmentTable)->updateOrInsert(
                [
                    'crew_record_id' => $recordId,
                    'month_key' => $monthKey,
                    'day' => $day,
                    'sort_order' => (int) ($segment['sort_order'] ?? 100),
                ],
                $segmentPayload
            );
        }
    }

    protected function backfillNormalizedCrewTables(): void
    {
        if (!Schema::hasTable($this->memberTable) || !Schema::hasTable($this->table)) {
            return;
        }

        $rows = DB::table($this->table)->get();
        foreach ($rows as $row) {
            $payload = $this->payloadFromRow($row);

            $this->syncCrewMember((string) $row->record_id, $payload, (string) ($row->depot ?? 'HQ'), $row);
            $this->syncCrewStatusSegments((string) $row->record_id, $payload, $row);

            $status = $this->normalizeText($payload['status'] ?? '');
            if ($status !== '') {
                $historyExists = DB::table($this->historyTable)
                    ->where('crew_record_id', $row->record_id)
                    ->exists();

                if (!$historyExists) {
                    DB::table($this->historyTable)->insert([
                        'history_id' => (string) Str::uuid(),
                        'crew_record_id' => (string) $row->record_id,
                        'crew_id' => (string) ($payload['id'] ?? $row->crew_id ?? $row->record_id),
                        'depot_code' => (string) ($payload['depot'] ?? $row->depot ?? 'HQ'),
                        'status_code' => $status,
                        'reason_code' => null,
                        'notes' => $this->normalizeText($payload['notes'] ?? '') ?: null,
                        'effective_at' => $row->updated_at ?: now(),
                        'metadata' => json_encode([
                            'shift' => $payload['shift'] ?? null,
                            'trainType' => $payload['trainType'] ?? null,
                            'route' => $payload['route'] ?? null,
                        ]),
                        'created_at' => $row->created_at ?: now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    protected function decodePayload(object $row): array
    {
        $payload = $this->payloadFromRow($row);

        $assignment = DB::table($this->shiftTable)
            ->where('crew_record_id', $row->record_id)
            ->first();

        $payload['id'] = $payload['id'] ?? ($row->crew_id ?: $row->record_id);
        $payload['depot'] = $payload['depot'] ?? $row->depot;
        $payload['staff_number'] = $payload['staff_number'] ?? $row->staff_number;
        $payload['shift'] = $payload['shift'] ?? ($assignment->shift ?? '');
        $payload['lastUpdated'] = $payload['lastUpdated'] ?? $row->updated_at;

        return $payload;
    }

    protected function normalizeRecordId(string $recordId): string
    {
        return trim($recordId);
    }

    protected function resolveSegmentDate(string $monthKey, int $day): string
    {
        $parts = preg_split('/[-\/]/', $monthKey) ?: [];
        $year = (int) ($parts[0] ?? date('Y'));
        $month = (int) ($parts[1] ?? date('m'));

        return sprintf('%04d-%02d-%02d', $year, $month, max(1, min(31, $day)));
    }

    protected function saveShiftAssignment(string $recordId, array $payload, string $depot): void
    {
        $shift = trim((string) ($payload['shift'] ?? ''));
        if ($shift === '') {
            DB::table($this->shiftTable)->where('crew_record_id', $recordId)->delete();
            return;
        }

        DB::table($this->shiftTable)->updateOrInsert(
            ['crew_record_id' => $recordId],
            [
                'record_id' => $recordId,
                'crew_id' => (string) ($payload['id'] ?? $recordId),
                'depot' => $depot,
                'shift' => $shift,
                'assigned_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    protected function syncCrewRecordMirror(string $recordId, array $payload, ?object $existing = null): void
    {
        $depot = $this->normalizeText($payload['depot'] ?? $existing?->depot ?? strtok($recordId, '_') ?: 'HQ') ?: 'HQ';

        DB::table($this->table)->updateOrInsert(
            ['record_id' => $recordId],
            [
                'depot' => $depot,
                'crew_id' => $this->normalizeText($payload['id'] ?? $existing?->crew_id ?? $recordId) ?: $recordId,
                'staff_number' => ($staffNumber = $this->normalizeText($payload['staff_number'] ?? $existing?->staff_number ?? '')) !== '' ? $staffNumber : null,
                'payload' => json_encode($payload),
                'updated_at' => now(),
                'created_at' => $existing ? $existing->created_at : now(),
            ]
        );

        $this->syncCrewMember($recordId, $payload, $depot, $existing);
        $this->syncCrewStatusSegments($recordId, $payload, $existing);
        $this->appendStatusHistory($recordId, $payload, $existing);
    }

    public function index(Request $request): JsonResponse
    {
        return $this->normalizedIndex($request);
    }

    public function show(string $recordId): JsonResponse
    {
        return $this->normalizedShow($recordId);
    }

    public function normalizedIndex(Request $request): JsonResponse
    {
        $depot = trim((string) $request->query('depot', ''));
        if (!$this->hasGlobalCrewAccess($request)) {
            $depot = (string) ($request->user()?->depot_code ?? '');
        }
        $monthKey = trim((string) $request->query('monthKey', ''));
        $records = $this->loadCrewViewRows($depot, $monthKey);

        return response()->json(['crew' => $records]);
    }

    public function normalizedShow(string $recordId): JsonResponse
    {
        $this->ensureTables();

        $record = $this->loadCrewViewRecord($recordId);
        if (!$record) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $this->authorizeCrewDepot(request(), (string) ($record['depot'] ?? ''));
        return response()->json($record);
    }

    public function save(Request $request, string $recordId): JsonResponse
    {
        return $this->normalizedSave($request, $recordId);
    }

    public function delete(string $recordId): JsonResponse
    {
        return $this->normalizedDelete($recordId);
    }

    public function normalizedSave(Request $request, string $recordId): JsonResponse
    {
        $this->ensureTables();

        $payload = $request->json()->all();
        if (!is_array($payload)) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $recordId = $this->normalizeRecordId($recordId);
        $existing = DB::table($this->table)->where('record_id', $recordId)->first();
        $decodedExisting = $existing ? $this->decodePayload($existing) : [];
        $merged = array_merge($decodedExisting, $payload);
        $merged['id'] = (string) ($merged['id'] ?? $recordId);
        $merged['depot'] = (string) ($merged['depot'] ?? $decodedExisting['depot'] ?? strtok($recordId, '_') ?: 'HQ');
        $merged['staff_number'] = trim((string) ($merged['staff_number'] ?? $decodedExisting['staff_number'] ?? ''));
        $merged['name'] = $merged['name'] ?? $payload['name'] ?? $recordId;
        $merged['grade'] = $merged['grade'] ?? $payload['grade'] ?? '';
        $merged['status'] = $merged['status'] ?? $payload['status'] ?? '';
        $merged['route'] = $merged['route'] ?? $payload['route'] ?? '';
        $merged['trainType'] = $merged['trainType'] ?? $payload['trainType'] ?? '';
        $merged['notes'] = $merged['notes'] ?? $payload['notes'] ?? '';
        $merged['since'] = $merged['since'] ?? $payload['since'] ?? '';
        $merged['monthly'] = $merged['monthly'] ?? $payload['monthly'] ?? [];
        $merged['monthKey'] = $merged['monthKey'] ?? $payload['monthKey'] ?? null;
        $merged['lastUpdated'] = $merged['lastUpdated'] ?? $payload['lastUpdated'] ?? now()->toIso8601String();
        $this->authorizeCrewDepot($request, (string) $merged['depot']);
        $shift = trim((string) ($merged['shift'] ?? ''));
        unset($merged['shift'], $merged['shift_assignment']);

        $this->syncCrewRecordMirror($recordId, $merged, $existing);

        $this->saveShiftAssignment($recordId, array_merge($merged, ['shift' => $shift]), $merged['depot']);

        return response()->json(['ok' => true, 'record' => $this->loadCrewViewRecord($recordId) ?? $merged]);
    }

    public function normalizedDelete(string $recordId): JsonResponse
    {
        $this->ensureTables();

        $recordId = $this->normalizeRecordId($recordId);
        $existing = DB::table($this->table)->where('record_id', $recordId)->first();
        if ($existing) {
            $this->authorizeCrewDepot(request(), (string) ($this->payloadFromRow($existing)['depot'] ?? $existing->depot));
        }
        DB::table($this->shiftTable)->where('crew_record_id', $recordId)->delete();
        DB::table($this->memberTable)->where('record_id', $recordId)->delete();
        DB::table($this->table)->where('record_id', $recordId)->delete();

        return response()->json(['ok' => true]);
    }
}
