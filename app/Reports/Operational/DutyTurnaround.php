<?php

namespace App\Reports\Operational;

use App\Reports\BaseReport;
use App\User;
use Illuminate\Http\Request;

class DutyTurnaround extends BaseReport
{
    public function slug(): string
    {
        return 'duty-turnaround';
    }

    public function title(): string
    {
        return 'Duty Cycle & Turnaround';
    }

    public function icon(): string
    {
        return '🔄';
    }

    public function category(): string
    {
        return 'Operations';
    }

    public function description(): string
    {
        return 'Rest frequency per crew: number of rest stays, average rest duration, and the turnaround time between consecutive rests.';
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
        ];
    }

    public function generate(Request $request, User $user, array $filterValues): array
    {
        $from = $filterValues['from'] ?? date('Y-m-01');
        $to = $filterValues['to'] ?? date('Y-m-d');
        if ($to < $from) {
            $to = $from;
        }

        $depotFilter = ($filterValues['depot'] ?? '') !== '' ? $filterValues['depot'] : null;
        $stays = $this->completedStays($from, $to, null, $this->visibleDepots($user));

        if ($depotFilter) {
            $stays = $stays->filter(fn ($row) => ($row['room_depot'] ?? null) === $depotFilter);
        }

        $rows = [];
        $gaps = [];
        foreach ($stays->groupBy('staff_no') as $staffNo => $crewStays) {
            $crewStays = $crewStays->sortBy(fn ($row) => ($row['arrival_date'].' '.$row['arrival_time']));
            $ordered = $crewStays->values();
            $stayHours = [];
            $turnarounds = [];

            for ($i = 0; $i < $ordered->count(); $i++) {
                $row = $ordered[$i];
                if ($row['stay_hours'] !== null) {
                    $stayHours[] = $row['stay_hours'];
                }
                if ($i > 0) {
                    $previous = $ordered[$i - 1];
                    $prevDeparture = strtotime($previous['departure_date'].' '.$previous['departure_time']);
                    $nextArrival = strtotime($row['arrival_date'].' '.$row['arrival_time']);
                    if ($prevDeparture !== false && $nextArrival !== false && $nextArrival > $prevDeparture) {
                        $turnarounds[] = ($nextArrival - $prevDeparture) / 3600;
                    }
                }
            }

            $last = $ordered->last();
            $rows[] = [
                'crew' => $last['name'] ?? '—',
                'staff_no' => $staffNo,
                'depot' => $last['crew_depot'] ?? '—',
                'stays' => number_format($ordered->count()),
                'avg_stay_h' => $stayHours ? $this->dec(array_sum($stayHours) / count($stayHours), 1) : '—',
                'avg_turnaround_h' => $turnarounds ? $this->dec(array_sum($turnarounds) / count($turnarounds), 0) : '—',
                'last_rest' => $last['departure_date'],
            ];

            $gaps = array_merge($gaps, $turnarounds);
        }

        usort($rows, fn ($a, $b) => (int) ($b['last_rest'] ?? '') <=> (int) ($a['last_rest'] ?? ''));

        $sections = [];
        if ($rows === []) {
            $sections[] = ['title' => 'No data', 'note' => 'No completed rest stays in the selected window.', 'columns' => [], 'rows' => []];
        } else {
            $sections[] = ['title' => 'Crew rest cycles', 'note' => 'Turnaround is the gap between the end of one rest stay and the start of the next. Shorter turnaround indicates busier rostering.', 'columns' => ['Crew', 'Staff No', 'Depot', 'Rest stays', 'Avg rest (h)', 'Avg turnaround (h)', 'Last rest'], 'rows' => $rows];
        }

        return [
            'kpis' => [
                ['label' => 'Crews rested', 'value' => number_format(count($rows))],
                ['label' => 'Rest stays', 'value' => number_format($stays->count())],
                ['label' => 'Avg turnaround', 'value' => $gaps ? $this->dec(array_sum($gaps) / count($gaps), 0).'h' : '—'],
                ['label' => 'Longest turnaround', 'value' => $gaps ? $this->dec(max($gaps), 0).'h' : '—'],
            ],
            'sections' => $sections,
            'filters_applied' => ['Period' => $from.' → '.$to, 'Depot' => $depotFilter ?? 'All'],
            'as_of' => date('Y-m-d H:i'),
        ];
    }
}