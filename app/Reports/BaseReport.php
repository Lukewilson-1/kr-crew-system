<?php

namespace App\Reports;

use App\Models\Room;
use App\User;
use Illuminate\Http\Request;

abstract class BaseReport implements Contracts\SystemReport
{
    public function icon(): string
    {
        return '📄';
    }

    public function description(): string
    {
        return '';
    }

    public function filterOptions(User $user, string $key): array
    {
        return in_array($key, ['depot', 'room'], true)
            ? $this->scopeOptions($key, $user)
            : [];
    }

    /**
     * Depot codes the user may see. Global/HQ users see everything; others are
     * limited to their own depot. Returns null meaning "no restriction".
     */
    protected function visibleDepots(?User $user): ?array
    {
        if (! $user || ! $user->is_active) {
            return [];
        }

        if ($user->isGlobalAccess()) {
            return null;
        }

        $code = trim((string) $user->depot_code);
        if ($code === '') {
            return [];
        }

        return [$code];
    }

    /**
     * Room ids the user may see, mirroring the running-rooms module scope.
     * Attendants only see their own room; depot users their depot's rooms.
     */
    protected function visibleRoomIds(User $user): array
    {
        if ($user->isAttendant()) {
            return [(int) $user->room_id];
        }

        $query = Room::query();
        if (! $user->isGlobalAccess()) {
            $depot = trim((string) $user->depot_code);
            if ($depot === '') {
                return [];
            }
            $query->where('depot_code', $depot);
        }

        return $query->pluck('id')->all();
    }

    /** Read filter values from the query string with per-type coercion. */
    protected function filterValues(Request $request, User $user): array
    {
        $values = [];
        foreach ($this->filters() as $filter) {
            $key = $filter['key'];
            $value = $request->query($key, $filter['default'] ?? null);

            if ($filter['type'] === 'date') {
                $value = $this->coerceDate($value);
            } elseif ($filter['type'] === 'month') {
                $value = $this->coerceMonth($value);
            }

            $values[$key] = $value;
        }

        return $values;
    }

    protected function coerceDate(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    protected function coerceMonth(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }

    protected function scopeOptions(string $key, User $user): array
    {
        if ($key === 'depot') {
            $options = [];
            $query = \App\Models\Depot::query()->orderBy('depot_name')->get(['depot_code', 'depot_name']);
            foreach ($query as $depot) {
                $options[$depot->depot_code] = $depot->depot_name.' ('.$depot->depot_code.')';
            }

            return $options;
        }

        $options = [];
        $rooms = Room::query()
            ->whereIn('id', $this->visibleRoomIds($user))
            ->orderBy('name')
            ->get(['id', 'name']);

        foreach ($rooms as $room) {
            $options[(string) $room->id] = $room->name;
        }

        return $options;
    }

    protected function dec(mixed $value, int $decimals = 1): string
    {
        return number_format((float) $value, $decimals);
    }

    protected function pct(mixed $numerator, mixed $denominator, int $decimals = 0): string
    {
        $denominator = (float) $denominator;

        if ($denominator <= 0) {
            return '—';
        }

        return number_format(((float) $numerator / $denominator) * 100, $decimals).'%';
    }

    public function resolveFilters(Request $request, User $user): array
    {
        return $this->filterValues($request, $user);
    }

    /**
     * All completed running-room stays within the given window (optionally
     * scoped by room/depot), each row carrying the room depot and the crew's
     * home depot so rest requirements can be resolved.
     */
    protected function completedStays(?string $from, ?string $to, ?array $roomIds, ?array $depots): \Illuminate\Support\Collection
    {
        $query = \Illuminate\Support\Facades\DB::table('attendance_records as ar')
            ->leftJoin('rooms as r', 'r.id', '=', 'ar.room_id')
            ->leftJoin('crew_members as cm', 'cm.staff_number', '=', 'ar.staff_no')
            ->where('ar.status', 'out')
            ->where('ar.departure_date', '>=', $from ?? '1900-01-01')
            ->where('ar.departure_date', '<=', $to ?? '2999-12-31')
            ->whereNotNull('ar.arrival_time')
            ->whereNotNull('ar.departure_time')
            ->select(
                'ar.*',
                'r.name as room_name',
                'r.depot_code as room_depot',
                'r.beds',
                'cm.depot_code as crew_depot'
            );

        if ($roomIds !== null) {
            $query->whereIn('ar.room_id', $roomIds);
        }

        if ($depots !== null) {
            $query->whereIn('r.depot_code', $depots);
        }

        return $query->get()->map(function ($row) {
            $stayHours = $this->stayHoursOf($row);
            $required = $this->requiredRestHours($row->crew_depot, $row->room_depot);

            return array_merge((array) $row, [
                'stay_hours' => $stayHours,
                'required_hours' => $required,
                'compliant' => $stayHours !== null && $stayHours >= $required,
            ]);
        });
    }

    /** Duration of a completed run-room stay in (fractional) hours, or null. */
    protected function stayHoursOf(object $row): ?float
    {
        $arrival = strtotime($row->arrival_date.' '.$row->arrival_time);
        $departure = strtotime($row->departure_date.' '.$row->departure_time);

        if ($arrival === false || $departure === false || $departure < $arrival) {
            return null;
        }

        return round(($departure - $arrival) / 3600, 2);
    }

    /** Rest requirement: 12h at the crew's home depot, 10h away. */
    protected function requiredRestHours(?string $crewDepot, ?string $roomDepot): float
    {
        if ($crewDepot !== null && $roomDepot !== null && strcasecmp(trim($crewDepot), trim($roomDepot)) === 0) {
            return 12.0;
        }

        return 10.0;
    }

    /** Effective start-of-day string for a given date. */
    protected function dayStart(string $date): string
    {
        return $date.' 00:00:00';
    }

    /** How many of the given stays occupied beds on the given day (Y-m-d). */
    protected function occupiedCount(array $stays, string $day): int
    {
        $count = 0;
        foreach ($stays as $stay) {
            if ((string) $stay->arrival_date > $day) {
                continue;
            }
            if ($stay->status === 'out' && $stay->departure_date !== null && (string) $stay->departure_date < $day) {
                continue;
            }
            $count++;
        }

        return $count;
    }
}