<?php

namespace App\Services;

use App\Models\CrewStatusSegment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CrewStatusService
{
    protected string $segmentTable = 'crew_status_segments';

    protected string $historyTable = 'crew_status_history';

    /**
     * Allowed status codes for crew duty positions.
     */
    public const STATUSES = ['BK', 'SB', 'R', 'L', 'SK', 'ABS', 'T', 'NTB', 'TO'];

    /**
     * Upsert a set of status segments for a crew record/month, applying validation
     * and time normalization. Returns the persisted segment keys that changed.
     *
     * @param  array  $segments  list of segment arrays (day, sort_order, status_code, ...)
     * @param  string  $recordId  crew_records.record_id
     * @param  string  $monthKey  e.g. "2026-09"
     * @param  array  $base  crew payload fields used to fill crew_id/depot fallbacks
     * @return array  created/updated segment rows
     */
    public function syncSegments(string $recordId, array $segments, string $monthKey, array $base = []): array
    {
        if (! Schema::hasTable($this->segmentTable)) {
            return [];
        }

        $currentMonthKey = now()->format('Y-m');
        $isCurrentMonth = $monthKey === $currentMonthKey;
        $daysInMonth = $this->daysInMonth($monthKey);

        // Cache column-existence checks ONCE (INFORMATION_SCHEMA is extremely slow
        // on shared hosting — previously called inside the per-segment loop, which
        // meant 6 queries per segment × 30+ segments = 180+ slow queries per save).
        $colSegmentId = $this->isStringColumn('segment_id');
        $colDate = Schema::hasColumn($this->segmentTable, 'date');
        $colStatus = Schema::hasColumn($this->segmentTable, 'status');
        $colNote = Schema::hasColumn($this->segmentTable, 'note');
        $colStartTime = Schema::hasColumn($this->segmentTable, 'start_time');
        $colEndTime = Schema::hasColumn($this->segmentTable, 'end_time');

        // Bulk-fetch existing segments for this crew+month in ONE query, then
        // compare in PHP. This replaces N per-segment SELECT queries with one.
        $existingByDaySort = [];
        $existingRows = DB::table($this->segmentTable)
            ->where('crew_record_id', $recordId)
            ->where('month_key', $monthKey)
            ->get(['day', 'sort_order', 'status_code', 'start_time', 'end_time', 'train_type', 'route', 'notes', 'segment_id', 'created_at', 'depot_code']);
        foreach ($existingRows as $row) {
            $existingByDaySort[(int) $row->day . '|' . (int) $row->sort_order] = $row;
        }

        $touched = [];

        foreach ($segments as $segment) {
            if (! is_array($segment)) {
                continue;
            }

            $day = isset($segment['day']) ? (int) $segment['day'] : 0;
            $statusCode = trim((string) ($segment['status_code'] ?? $segment['status'] ?? ''));
            if ($day < 1 || $day > $daysInMonth) {
                continue;
            }

            // Blank status means "remove all segments for this day".
            if ($statusCode === '') {
                // Only issue DELETE if there are actually stored segments for this day.
                $hasStored = false;
                foreach ($existingByDaySort as $key => $row) {
                    if ((int) $row->day === $day) {
                        $hasStored = true;
                        break;
                    }
                }
                if ($hasStored) {
                    DB::table($this->segmentTable)
                        ->where('crew_record_id', $recordId)
                        ->where('month_key', $monthKey)
                        ->where('day', $day)
                        ->delete();
                }
                continue;
            }

            // Reject statuses outside the allowed set so reports stay clean.
            if (! in_array($statusCode, self::STATUSES, true)) {
                continue;
            }

            $sortOrder = (int) ($segment['sort_order'] ?? 100);

            $existingSegment = $existingByDaySort[$day . '|' . $sortOrder] ?? null;

            $startTime = $this->normalizeTime($segment['start_time'] ?? $segment['book_time'] ?? null);
            $endTime = $this->normalizeTime($segment['end_time'] ?? $segment['book_time'] ?? null);

            if ($startTime === null) {
                $startTime = '00:00';
            }
            if ($endTime === null) {
                $endTime = '23:59';
            }

            // Fast path: skip writing if nothing changed compared to stored.
            if ($existingSegment) {
                $storedStatus = trim((string) ($existingSegment->status_code ?? ''));
                $storedStart = (string) ($existingSegment->start_time ?? '');
                $storedEnd = (string) ($existingSegment->end_time ?? '');
                $storedRoute = trim((string) ($existingSegment->route ?? ''));
                $storedNotes = trim((string) ($existingSegment->notes ?? ''));
                $storedTrainType = trim((string) ($existingSegment->train_type ?? ''));

                if ($statusCode === $storedStatus
                    && $startTime === $storedStart
                    && $endTime === $storedEnd
                    && $this->clean((string) ($segment['route'] ?? '')) === $storedRoute
                    && $this->clean((string) ($segment['notes'] ?? '')) === $storedNotes
                    && $this->clean((string) ($segment['train_type'] ?? '')) === $storedTrainType) {
                    // Segment unchanged — skip DB write entirely.
                    continue;
                }
            }

            $segmentPayload = [
                'crew_id' => $this->clean((string) ($base['id'] ?? $segment['crew_id'] ?? $existingSegment->crew_id ?? $recordId)) ?: $recordId,
                'depot_code' => $this->clean((string) ($base['depot'] ?? $segment['depot_code'] ?? $existingSegment->depot_code ?? '')) ?: null,
                'month_key' => $monthKey,
                'day' => $day,
                'sort_order' => $sortOrder,
                'status_code' => $statusCode,
                'train_type' => $this->clean((string) ($segment['train_type'] ?? '')) ?: null,
                'route' => $this->clean((string) ($segment['route'] ?? '')) ?: null,
                'book_time' => $this->clean((string) ($segment['book_time'] ?? '')) ?: null,
                'rest_started_at' => $segment['rest_started_at'] ?? null,
                'away_depot' => $this->clean((string) ($segment['away_depot'] ?? '')) ?: null,
                'notes' => $this->clean((string) ($segment['notes'] ?? '')) ?: null,
                'metadata' => $segment['metadata'] ?? json_encode([]),
                'created_at' => $existingSegment ? $existingSegment->created_at : now(),
                'updated_at' => now(),
            ];

            if ($colSegmentId) {
                $segmentPayload['segment_id'] = $existingSegment
                    ? (string) $existingSegment->segment_id
                    : (string) ($segment['segment_id'] ?? Str::uuid());
            }
            if ($colDate) {
                $segmentPayload['date'] = $this->resolveSegmentDate($monthKey, $day);
            }
            if ($colStatus) {
                $segmentPayload['status'] = $statusCode;
            }
            if ($colNote) {
                $segmentPayload['note'] = $this->clean((string) ($segment['notes'] ?? '')) ?: null;
            }
            if ($colStartTime) {
                $segmentPayload['start_time'] = $startTime;
            }
            if ($colEndTime) {
                $segmentPayload['end_time'] = $endTime;
            }

            DB::table($this->segmentTable)->updateOrInsert(
                [
                    'crew_record_id' => $recordId,
                    'month_key' => $monthKey,
                    'day' => $day,
                    'sort_order' => $sortOrder,
                ],
                $segmentPayload
            );

            $touched[] = $segmentPayload;
        }

        return $touched;
    }

    /**
     * Write one crew_status_history row for each status segment so the full
     * event-by-event activity is reconstructible.
     */
    public function appendSegmentHistory(string $recordId, array $base, array $segments): void
    {
        if (! Schema::hasTable($this->historyTable) || empty($segments)) {
            return;
        }

        foreach ($segments as $segment) {
            $statusCode = trim((string) ($segment['status_code'] ?? $segment['status'] ?? ''));
            if ($statusCode === '' || ! in_array($statusCode, self::STATUSES, true)) {
                continue;
            }

            $day = (int) ($segment['day'] ?? 0);
            $monthKey = trim((string) ($segment['month_key'] ?? $base['monthKey'] ?? ''));
            $date = $this->resolveSegmentDate($monthKey ?: now()->format('Y-m'), $day ?: (int) now()->format('j'));

            DB::table($this->historyTable)->insert([
                'history_id' => (string) Str::uuid(),
                'crew_record_id' => $recordId,
                'crew_id' => $this->clean((string) ($base['id'] ?? $recordId)) ?: $recordId,
                'depot_code' => $this->clean((string) ($base['depot'] ?? '')) ?: null,
                'status_code' => $statusCode,
                'reason_code' => $this->clean((string) ($segment['reason_code'] ?? '')) ?: null,
                'notes' => $this->clean((string) ($segment['notes'] ?? $segment['note'] ?? '')) ?: null,
                'effective_at' => $this->segmentEffectiveAt($monthKey, $day, $segment),
                'metadata' => json_encode([
                    'segment_id' => $segment['segment_id'] ?? null,
                    'month_key' => $monthKey ?: null,
                    'day' => $day ?: null,
                    'date' => $date,
                    'start_time' => $segment['start_time'] ?? null,
                    'end_time' => $segment['end_time'] ?? null,
                    'train_type' => $segment['train_type'] ?? null,
                    'route' => $segment['route'] ?? null,
                    'shift' => $segment['shift'] ?? null,
                    'updatedBy' => $base['updatedBy'] ?? null,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Validate that daytime segments are contiguous (no gaps/overlaps) within each day.
     * Returns an associative array of day => error message, empty when valid.
     */
    public function validateContiguity(array $segments): array
    {
        $errors = [];

        $byDay = [];
        foreach ($segments as $segment) {
            if (! is_array($segment)) {
                continue;
            }
            $day = (int) ($segment['day'] ?? 0);
            if ($day < 1) {
                continue;
            }
            $byDay[$day][] = $segment;
        }

        foreach ($byDay as $day => $list) {
            usort($list, fn ($a, $b) => $this->timeToMinutes($a['start_time'] ?? '00:00') <=> $this->timeToMinutes($b['start_time'] ?? '00:00'));
            $prevEnd = -1;
            foreach ($list as $segment) {
                $start = $this->timeToMinutes($segment['start_time'] ?? '00:00');
                $end = $this->timeToMinutes($segment['end_time'] ?? '23:59');
                if ($end <= $start) {
                    $errors[$day] = $errors[$day] ?? "Segment '{$segment['status_code']}' ends before it starts on day {$day}.";
                }
                if ($prevEnd > $start) {
                    $errors[$day] = $errors[$day] ?? "Overlapping segments on day {$day}.";
                }
                if ($start < $prevEnd) {
                    $errors[$day] = $errors[$day] ?? "Gap between segments on day {$day}.";
                }
                $prevEnd = max($prevEnd, $end);
            }
        }

        return $errors;
    }

    protected function segmentEffectiveAt(string $monthKey, int $day, array $segment): \Carbon\CarbonInterface
    {
        $date = $this->resolveSegmentDate($monthKey ?: now()->format('Y-m'), $day ?: (int) now()->format('j'));
        $time = $segment['start_time'] ?? '00:00';

        try {
            return \Illuminate\Support\Carbon::parse("{$date} {$time}");
        } catch (\Throwable $e) {
            return now();
        }
    }

    protected function normalizeTime(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '--:--') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
            $h = (int) $m[1];
            $mi = (int) $m[2];
            if ($h < 0 || $h > 23 || $mi < 0 || $mi > 59) {
                return null;
            }
            return sprintf('%02d:%02d', $h, $mi);
        }

        return null;
    }

    protected function timeToMinutes(?string $value): int
    {
        if (preg_match('/^(\d{1,2}):(\d{2})$/', (string) $value, $m)) {
            return max(0, min(1440, (int) $m[1] * 60 + (int) $m[2]));
        }
        return 0;
    }

    protected function clean(string $value): string
    {
        return trim($value);
    }

    protected function isStringColumn(string $column): bool
    {
        try {
            return in_array(Schema::getColumnType($this->segmentTable, $column), ['string', 'varchar', 'text'], true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function resolveSegmentDate(string $monthKey, int $day): string
    {
        $parts = preg_split('/[-\/]/', $monthKey) ?: [];
        $year = (int) ($parts[0] ?? now()->format('Y'));
        $month = (int) ($parts[1] ?? now()->format('m'));
        $maxDay = $this->daysInMonth(sprintf('%04d-%02d', $year, $month));

        return sprintf('%04d-%02d-%02d', $year, $month, max(1, min($maxDay, $day)));
    }

    protected function daysInMonth(string $monthKey): int
    {
        $parts = preg_split('/[-\/]/', $monthKey) ?: [];
        $year = (int) ($parts[0] ?? now()->format('Y'));
        $month = (int) ($parts[1] ?? now()->format('m'));

        if ($year < 1 || $month < 1 || $month > 12) {
            return 31;
        }

        for ($day = 31; $day >= 28; $day--) {
            if (checkdate($month, $day, $year)) {
                return $day;
            }
        }

        return 31;
    }
}
