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

    protected function payloadFromRow(object $row): array
    {
        $payload = json_decode($row->payload ?? '{}', true);
        return is_array($payload) ? $payload : [];
    }

    protected function buildCrewViewRecord(object $row): array
    {
        $payload = $this->payloadFromRow($row);
        $member = DB::table($this->memberTable)->where('record_id', $row->record_id)->first();
        $assignment = DB::table($this->shiftTable)->where('crew_record_id', $row->record_id)->first();
        $latestHistory = DB::table($this->historyTable)
            ->where('crew_record_id', $row->record_id)
            ->orderByDesc('effective_at')
            ->orderByDesc('created_at')
            ->first();

        $record = $payload;
        $record['id'] = $payload['id'] ?? ($row->crew_id ?: $row->record_id);
        $record['record_id'] = $row->record_id;
        $record['name'] = $member?->display_name ?: trim((string) ($member?->first_name ?? '') . ' ' . (string) ($member?->last_name ?? '')) ?: ($payload['name'] ?? $row->record_id);
        $record['grade'] = $member?->designation_code ?? ($payload['grade'] ?? '');
        $record['depot'] = $member?->depot_code ?? $row->depot;
        $record['staff_number'] = $member?->staff_number ?? $row->staff_number ?? ($payload['staff_number'] ?? '');
        $record['shift'] = $assignment->shift ?? ($payload['shift'] ?? '');
        $record['status'] = $payload['status'] ?? ($latestHistory->status_code ?? '');
        $record['route'] = $payload['route'] ?? '';
        $record['trainType'] = $payload['trainType'] ?? '';
        $record['bookTime'] = $payload['bookTime'] ?? '';
        $record['notes'] = $payload['notes'] ?? '';
        $record['since'] = $payload['since'] ?? '';
        $record['restStarted'] = $payload['restStarted'] ?? null;
        $record['awayDepot'] = $payload['awayDepot'] ?? null;
        $record['monthly'] = $payload['monthly'] ?? [];
        $record['monthKey'] = $payload['monthKey'] ?? null;
        $record['lastUpdated'] = $payload['lastUpdated'] ?? $row->updated_at;

        return $record;
    }

    protected function loadCrewViewRows(?string $depot = null, ?string $monthKey = null): array
    {
        $query = DB::table($this->table);
        if ($depot !== null && $depot !== '') {
            $query->where('depot', $depot);
        }

        $rows = $query->orderBy('depot')->orderBy('crew_id')->get();
        $records = [];

        foreach ($rows as $row) {
            $record = $this->buildCrewViewRecord($row);
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

        if ($displayName === '' && ($firstName !== '' || $lastName !== '')) {
            $displayName = trim($firstName . ' ' . $lastName);
        }

        DB::table($this->memberTable)->updateOrInsert(
            ['record_id' => $recordId],
            [
                'crew_id' => $this->normalizeText($payload['id'] ?? $existing?->crew_id ?? $recordId) ?: $recordId,
                'staff_number' => ($staffNumber = $this->normalizeText($payload['staff_number'] ?? $existing?->staff_number ?? '')) !== '' ? $staffNumber : null,
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
                'created_at' => $existing?->created_at ?: now(),
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

    protected function backfillNormalizedCrewTables(): void
    {
        if (!Schema::hasTable($this->memberTable) || !Schema::hasTable($this->table)) {
            return;
        }

        $rows = DB::table($this->table)->get();
        foreach ($rows as $row) {
            $payload = $this->payloadFromRow($row);

            $this->syncCrewMember((string) $row->record_id, $payload, (string) ($row->depot ?? 'HQ'), $row);

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
        $this->ensureTables();
        $this->backfillNormalizedCrewTables();

        $depot = trim((string) $request->query('depot', ''));
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
        DB::table($this->shiftTable)->where('crew_record_id', $recordId)->delete();
        DB::table($this->memberTable)->where('record_id', $recordId)->delete();
        DB::table($this->table)->where('record_id', $recordId)->delete();

        return response()->json(['ok' => true]);
    }
}