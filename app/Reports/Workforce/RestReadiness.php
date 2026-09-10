<?php

namespace App\Reports\Workforce;

use App\Models\Room;
use App\Reports\BaseReport;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestReadiness extends BaseReport
{
    public function slug(): string
    {
        return 'rest-readiness';
    }

    public function title(): string
    {
        return 'Rest Readiness';
    }

    public function icon(): string
    {
        return '⏳';
    }

    public function category(): string
    {
        return 'Crew & Workforce';
    }

    public function description(): string
    {
        return 'Live snapshot of crew currently on rest: elapsed rest time versus the requirement, flagged when ready to resume duty.';
    }

    public function allows(User $user): bool
    {
        return $user->is_active;
    }

    public function filters(): array
    {
        return [
            ['key' => 'depot', 'label' => 'Depot', 'type' => 'select', 'default' => ''],
            ['key' => 'room', 'label' => 'Room', 'type' => 'select', 'default' => ''],
        ];
    }

    public function generate(Request $request, User $user, array $filterValues): array
    {
        $now = now();
        $visibleRooms = $this->visibleRoomIds($user);
        $depotFilter = ($filterValues['depot'] ?? '') !== '' ? $filterValues['depot'] : null;
        $roomFilter = ($filterValues['room'] ?? '') !== '' ? (int) $filterValues['room'] : null;

        $query = DB::table('attendance_records as ar')
            ->join('rooms as r', 'r.id', '=', 'ar.room_id')
            ->leftJoin('crew_members as cm', 'cm.staff_number', '=', 'ar.staff_no')
            ->where('ar.status', 'in')
            ->whereIn('ar.room_id', $visibleRooms)
            ->when($depotFilter, fn ($q) => $q->where('r.depot_code', $depotFilter))
            ->when($roomFilter, fn ($q) => $q->where('ar.room_id', $roomFilter))
            ->select(
                'ar.*',
                'r.name as room_name',
                'r.depot_code as room_depot',
                'r.beds',
                'cm.depot_code as crew_depot'
            )
            ->get();

        $rows = [];
        $ready = 0;
        $waiting = 0;
        $elapsedList = [];

        foreach ($query as $guest) {
            $arrival = strtotime($guest->arrival_date.' '.($guest->arrival_time ?? '00:00:00'));
            $elapsed = $arrival !== false && $arrival <= $now->getTimestamp()
                ? round(($now->getTimestamp() - $arrival) / 3600, 2)
                : 0.0;

            $required = $this->requiredRestHours($guest->crew_depot, $guest->room_depot);
            $isReady = $elapsed >= $required;
            $isReady ? $ready++ : $waiting++;
            $elapsedList[] = $elapsed;

            $rows[] = [
                'crew' => $guest->name,
                'staff_no' => $guest->staff_no,
                'room' => $guest->room_name,
                'depot' => $guest->room_depot,
                'arrived' => $guest->arrival_date.' '.($guest->arrival_time ?? ''),
                'elapsed_h' => $this->dec($elapsed, 1),
                'required_h' => $this->dec($required, 0),
                'status' => $isReady ? 'READY' : 'Resting',
            ];
        }

        usort($rows, fn ($a, $b) => (float) $b['elapsed_h'] <=> (float) $a['elapsed_h']);

        $sections = [];
        if ($rows === []) {
            $sections[] = ['title' => 'No data', 'note' => 'No crew currently checked in at the running rooms.', 'columns' => [], 'rows' => []];
        } else {
            $sections[] = ['title' => 'Crew currently on rest', 'note' => 'Requirement is 12h at the crew home depot, 10h away. READY crews have completed their required rest.', 'columns' => ['Crew', 'Staff No', 'Room', 'Depot', 'Arrived', 'Elapsed (h)', 'Required (h)', 'Status'], 'rows' => $rows];
        }

        return [
            'kpis' => [
                ['label' => 'On rest now', 'value' => number_format($query->count())],
                ['label' => 'Ready', 'value' => number_format($ready), 'sub' => 'Waiting '.number_format($waiting)],
                ['label' => 'Avg elapsed', 'value' => $elapsedList ? $this->dec(array_sum($elapsedList) / count($elapsedList), 1).'h' : '—'],
            ],
            'sections' => $sections,
            'filters_applied' => ['Depot' => $depotFilter ?? 'All', 'Room' => $roomFilter ? Room::find($roomFilter)?->name : 'All'],
            'as_of' => $now->format('Y-m-d H:i'),
        ];
    }
}