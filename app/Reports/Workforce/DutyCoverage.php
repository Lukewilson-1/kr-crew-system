<?php

namespace App\Reports\Workforce;

use App\Reports\BaseReport;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DutyCoverage extends BaseReport
{
    private const ON_DUTY = ['BK', 'SB', 'NTB', 'TO'];
    private const RESTING = ['R'];
    private const LEAVE = ['L'];
    private const ABSENT = ['SK', 'ABS'];
    private const TRAINING = ['T'];

    public function slug(): string
    {
        return 'duty-coverage';
    }

    public function title(): string
    {
        return 'Duty Coverage';
    }

    public function icon(): string
    {
        return '👷';
    }

    public function category(): string
    {
        return 'Crew & Workforce';
    }

    public function description(): string
    {
        return 'Daily on-duty, resting, leave, sick and absent crew per depot, so coverage gaps are visible at a glance.';
    }

    public function allows(User $user): bool
    {
        return $user->is_active;
    }

    public function filters(): array
    {
        return [
            ['key' => 'month', 'label' => 'Month', 'type' => 'month', 'default' => date('Y-m')],
            ['key' => 'depot', 'label' => 'Depot', 'type' => 'select', 'default' => ''],
        ];
    }

    public function generate(Request $request, User $user, array $filterValues): array
    {
        $month = $filterValues['month'] ?? date('Y-m');
        $from = $month.'-01';
        $to = date('Y-m-t', strtotime($month.'-01'));

        $depotFilter = ($filterValues['depot'] ?? '') !== '' ? $filterValues['depot'] : null;
        $visibleDepots = $this->visibleDepots($user);

        $onDutyList = "'".implode("','", self::ON_DUTY)."'";
        $restingList = "'".implode("','", self::RESTING)."'";
        $leaveList = "'".implode("','", self::LEAVE)."'";
        $absentList = "'".implode("','", self::ABSENT)."'";
        $trainingList = "'".implode("','", self::TRAINING)."'";

        $query = DB::table('crew_status_segments as cs')
            ->whereBetween('cs.date', [$from, $to])
            ->when($visibleDepots !== null, fn ($q) => $q->whereIn('cs.depot_code', $visibleDepots))
            ->when($depotFilter, fn ($q) => $q->where('cs.depot_code', $depotFilter))
            ->groupBy('cs.date', 'cs.depot_code')
            ->select(
                'cs.date',
                'cs.depot_code',
                DB::raw("COUNT(DISTINCT CASE WHEN cs.status_code IN ($onDutyList) THEN cs.crew_id END) as on_duty"),
                DB::raw("COUNT(DISTINCT CASE WHEN cs.status_code IN ($restingList) THEN cs.crew_id END) as resting"),
                DB::raw("COUNT(DISTINCT CASE WHEN cs.status_code IN ($leaveList) THEN cs.crew_id END) as leave_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN cs.status_code IN ($absentList) THEN cs.crew_id END) as absent"),
                DB::raw("COUNT(DISTINCT CASE WHEN cs.status_code IN ($trainingList) THEN cs.crew_id END) as training")
            )
            ->orderBy('cs.date')
            ->orderBy('cs.depot_code')
            ->get();

        $rows = [];
        $totals = ['on_duty' => 0, 'resting' => 0, 'leave' => 0, 'absent' => 0, 'training' => 0, 'days' => 0];

        foreach ($query as $row) {
            $rows[] = [
                'date' => $row->date,
                'depot' => $row->depot_code,
                'on_duty' => number_format((int) $row->on_duty),
                'resting' => number_format((int) $row->resting),
                'leave' => number_format((int) $row->leave_count),
                'sick_absent' => number_format((int) $row->absent),
                'training' => number_format((int) $row->training),
            ];
            $totals['on_duty'] += (int) $row->on_duty;
            $totals['resting'] += (int) $row->resting;
            $totals['leave'] += (int) $row->leave_count;
            $totals['absent'] += (int) $row->absent;
            $totals['training'] += (int) $row->training;
            $totals['days']++;
        }

        $sections = [];
        if ($rows === []) {
            $sections[] = ['title' => 'No data', 'note' => 'No crew status segments in the selected month.', 'columns' => [], 'rows' => []];
        } else {
            $sections[] = ['title' => 'Daily coverage', 'note' => 'Counts are distinct crew per status group per day.', 'columns' => ['Date', 'Depot', 'On duty', 'Resting', 'Leave', 'Sick / Absent', 'Training'], 'rows' => $rows];
        }

        return [
            'kpis' => [
                ['label' => 'Crew-days on duty', 'value' => number_format($totals['on_duty'])],
                ['label' => 'Avg on duty / day', 'value' => $totals['days'] > 0 ? $this->dec($totals['on_duty'] / $totals['days'], 0) : '—'],
                ['label' => 'Sick / absent days', 'value' => number_format($totals['absent']), 'sub' => 'Leave '.number_format($totals['leave'])],
                ['label' => 'Resting days', 'value' => number_format($totals['resting'])],
            ],
            'sections' => $sections,
            'filters_applied' => ['Month' => $month, 'Depot' => $depotFilter ?? 'All'],
            'as_of' => date('Y-m-d H:i'),
        ];
    }
}