<?php

namespace App\Reports\Operational;

use App\Models\Room;
use App\Reports\BaseReport;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomOccupancy extends BaseReport
{
    public function slug(): string
    {
        return 'room-occupancy';
    }

    public function title(): string
    {
        return 'Room Occupancy & Peak Load';
    }

    public function icon(): string
    {
        return '🚪';
    }

    public function category(): string
    {
        return 'Operations';
    }

    public function description(): string
    {
        return 'Bed utilisation, average and peak occupancy per running room, with overflow days when demand exceeded available beds.';
    }

    public function allows(User $user): bool
    {
        return $user->is_active;
    }

    public function filters(): array
    {
        return [
            ['key' => 'from', 'label' => 'From', 'type' => 'date', 'default' => date('Y-m-01')],
            ['key' => 'to', 'label' => 'To', 'type' => 'date', 'default' => date('Y-m-d')],
            ['key' => 'depot', 'label' => 'Depot', 'type' => 'select', 'default' => ''],
            ['key' => 'room', 'label' => 'Room', 'type' => 'select', 'default' => ''],
        ];
    }

    public function generate(Request $request, User $user, array $filterValues): array
    {
        $from = $filterValues['from'] ?? date('Y-m-01');
        $to = $filterValues['to'] ?? date('Y-m-d');
        if ($to < $from) {
            $to = $from;
        }

        $visibleRooms = $this->visibleRoomIds($user);
        $depotFilter = ($filterValues['depot'] ?? '') !== '' ? $filterValues['depot'] : null;
        $roomFilter = ($filterValues['room'] ?? '') !== ''
            ? (int) $filterValues['room']
            : null;

        $roomQuery = Room::query()->whereIn('id', $visibleRooms);
        if ($depotFilter) {
            $roomQuery->where('depot_code', $depotFilter);
        }
        if ($roomFilter) {
            $roomQuery->where('id', $roomFilter);
        }
        $rooms = $roomQuery->orderBy('name')->get(['id', 'name', 'beds', 'depot_code']);

        $stays = DB::table('attendance_records')
            ->whereIn('room_id', $rooms->pluck('id'))
            ->where('arrival_date', '<=', $to)
            ->where(function ($q) use ($from) {
                $q->whereNull('departure_date')
                    ->orWhere('departure_date', '>=', $from);
            })
            ->get(['room_id', 'arrival_date', 'arrival_time', 'departure_date', 'departure_time', 'staff_no', 'status']);

        $days = [];
        for ($day = strtotime($from); $day <= strtotime($to); $day = strtotime('+1 day', $day)) {
            $days[] = date('Y-m-d', $day);
        }

        $byRoom = $stays->groupBy('room_id');
        $rows = [];
        $grand = ['admissions' => 0, 'bed_nights' => 0, 'beds' => 0, 'peak' => 0, 'overflow_days' => 0];

        foreach ($rooms as $room) {
            $roomStays = ($byRoom->get($room->id) ?? collect())->all();
            $admissions = count($roomStays);
            $peak = 0;
            $peakDate = null;
            $bedNights = 0;
            $overflowDays = 0;

            foreach ($days as $day) {
                $occupied = $this->occupiedCount($roomStays, $day);
                $bedNights += $occupied;
                if ($occupied > $room->beds) {
                    $overflowDays++;
                }
                if ($occupied > $peak) {
                    $peak = $occupied;
                    $peakDate = $day;
                }
            }

            $capacity = ($days === [] ? 0 : $room->beds * count($days));
            $occupancy = $capacity > 0 ? $this->dec(($bedNights / $capacity) * 100, 0).'%' : '—';

            $rows[] = [
                'room' => $room->name,
                'depot' => $room->depot_code,
                'beds' => number_format($room->beds),
                'admissions' => number_format($admissions),
                'bed_nights' => number_format($bedNights),
                'occupancy' => $occupancy,
                'peak' => number_format($peak).( $peakDate ? ' on '.$peakDate : ''),
                'overflow_days' => number_format($overflowDays),
            ];

            $grand['admissions'] += $admissions;
            $grand['bed_nights'] += $bedNights;
            $grand['beds'] += ($days === [] ? 0 : $room->beds * count($days));
            $grand['peak'] = max($grand['peak'], $peak);
            $grand['overflow_days'] += $overflowDays;
        }

        $sections = [];
        if ($rows === []) {
            $sections[] = ['title' => 'No data', 'note' => 'No occupancy recorded in the selected window.', 'columns' => [], 'rows' => []];
        } else {
            $sections[] = ['title' => 'Room utilisation', 'note' => 'Occupancy is average nightly bed usage over dry days in the window; peak shows the busiest single day.', 'columns' => ['Room', 'Depot', 'Beds', 'Admissions', 'Bed-nights', 'Occupancy', 'Peak', 'Overflow days'], 'rows' => $rows];
        }

        return [
            'kpis' => [
                ['label' => 'Admissions', 'value' => number_format($grand['admissions'])],
                ['label' => 'Bed-nights', 'value' => number_format($grand['bed_nights'])],
                ['label' => 'Avg occupancy', 'value' => $grand['beds'] > 0 ? $this->dec(($grand['bed_nights'] / $grand['beds']) * 100, 0).'%' : '—'],
                ['label' => 'Peak occupants', 'value' => number_format($grand['peak']), 'sub' => 'Overflow days '.number_format($grand['overflow_days'])],
            ],
            'sections' => $sections,
            'filters_applied' => ['Period' => $from.' → '.$to, 'Depot' => $depotFilter ?? 'All', 'Room' => $roomFilter ? Room::find($roomFilter)?->name : 'All'],
            'as_of' => date('Y-m-d H:i'),
        ];
    }
}