<?php

namespace App\Reports\Workforce;

use App\Reports\BaseReport;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbsenceHeatmap extends BaseReport
{
    private const ABSENCE = ['L', 'SK', 'ABS'];

    public function slug(): string
    {
        return 'absence-heatmap';
    }

    public function title(): string
    {
        return 'Absence & Leave Heatmap';
    }

    public function icon(): string
    {
        return '📅';
    }

    public function category(): string
    {
        return 'Crew & Workforce';
    }

    public function description(): string
    {
        return 'Daily leave, sick and absent crew per depot, highlighting recurring shortfalls and their cause.';
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

        $absenceList = "'".implode("','", self::ABSENCE)."'";

        $query = DB::table('crew_status_segments as cs')
            ->whereBetween('cs.date', [$from, $to])
            ->whereIn('cs.status_code', self::ABSENCE)
            ->when($visibleDepots !== null, fn ($q) => $q->whereIn('cs.depot_code', $visibleDepots))
            ->when($depotFilter, fn ($q) => $q->where('cs.depot_code', $depotFilter))
            ->groupBy('cs.date', 'cs.depot_code')
            ->select(
                'cs.date',
                'cs.depot_code',
                DB::raw("COUNT(DISTINCT CASE WHEN cs.status_code = 'L' THEN cs.crew_id END) as leave_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN cs.status_code = 'SK' THEN cs.crew_id END) as sick"),
                DB::raw("COUNT(DISTINCT CASE WHEN cs.status_code = 'ABS' THEN cs.crew_id END) as absent")
            )
            ->orderByDesc('cs.date')
            ->orderBy('cs.depot_code')
            ->get();

        $rows = [];
        $totals = ['leave' => 0, 'sick' => 0, 'absent' => 0];

        foreach ($query as $row) {
            $rows[] = [
                'date' => $row->date,
                'depot' => $row->depot_code,
                'leave_count' => number_format((int) $row->leave_count),
                'sick' => number_format((int) $row->sick),
                'absent' => number_format((int) $row->absent),
                'total' => number_format((int) $row->leave_count + (int) $row->sick + (int) $row->absent),
            ];
            $totals['leave'] += (int) $row->leave_count;
            $totals['sick'] += (int) $row->sick;
            $totals['absent'] += (int) $row->absent;
        }

        $sections = [];
        if ($rows === []) {
            $sections[] = ['title' => 'No data', 'note' => 'No leave, sick or absent segments in the selected month.', 'columns' => [], 'rows' => []];
        } else {
            $sections[] = ['title' => 'Daily absence', 'note' => 'Distinct crew per day in leave (L), sick (SK) or absent (ABS) status.', 'columns' => ['Date', 'Depot', 'Leave', 'Sick', 'Absent', 'Total'], 'rows' => $rows];
        }

        return [
            'kpis' => [
                ['label' => 'Leave days', 'value' => number_format($totals['leave'])],
                ['label' => 'Sick days', 'value' => number_format($totals['sick'])],
                ['label' => 'Absent days', 'value' => number_format($totals['absent'])],
                ['label' => 'Total absence', 'value' => number_format($totals['leave'] + $totals['sick'] + $totals['absent'])],
            ],
            'sections' => $sections,
            'filters_applied' => ['Month' => $month, 'Depot' => $depotFilter ?? 'All'],
            'as_of' => date('Y-m-d H:i'),
        ];
    }
}