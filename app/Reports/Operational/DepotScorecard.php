<?php

namespace App\Reports\Operational;

use App\Models\Depot;
use App\Models\Matter;
use App\Models\Room;
use App\Reports\BaseReport;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepotScorecard extends BaseReport
{
    public function slug(): string
    {
        return 'depot-scorecard';
    }

    public function title(): string
    {
        return 'Depot Scorecard';
    }

    public function icon(): string
    {
        return '🏢';
    }

    public function category(): string
    {
        return 'Operations';
    }

    public function description(): string
    {
        return 'Monthly operational snapshot per depot: active crew strength, running-room admissions, rest compliance and matters performance.';
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

        $visibleDepots = $this->visibleDepots($user);
        $depotFilter = ($filterValues['depot'] ?? '') !== '' ? $filterValues['depot'] : null;

        $depots = DB::table('depots as d')
            ->leftJoin('rooms as r', 'r.depot_code', '=', 'd.depot_code')
            ->when($visibleDepots !== null, fn ($q) => $q->whereIn('d.depot_code', $visibleDepots))
            ->when($depotFilter, fn ($q) => $q->where('d.depot_code', $depotFilter))
            ->groupBy('d.depot_code', 'd.depot_name')
            ->select('d.depot_code', 'd.depot_name')
            ->orderBy('d.depot_name')
            ->get();

        $rows = [];
        $totals = ['crews' => 0, 'admissions' => 0, 'stays' => 0, 'compliant' => 0, 'open' => 0, 'resolved' => 0];

        foreach ($depots as $depot) {
            $roomIds = Room::where('depot_code', $depot->depot_code)->pluck('id');

            $crews = DB::table('crew_members')
                ->where('depot_code', $depot->depot_code)
                ->where('is_active', 1)
                ->count();

            $admissions = DB::table('attendance_records as ar')
                ->join('rooms as r', 'r.id', '=', 'ar.room_id')
                ->where('r.depot_code', $depot->depot_code)
                ->whereBetween('ar.arrival_date', [$from, $to])
                ->count();

            $stays = $this->completedStays($from, $to, $roomIds->all(), null);

            $mattersQuery = Matter::whereIn('room_id', $roomIds);
            $openMatters = (clone $mattersQuery)->where('status', 'open')->count();
            $resolvedMatters = (clone $mattersQuery)->where('status', 'resolved')->count();
            $totalMatters = $openMatters + $resolvedMatters;

            $compliant = $stays->where('compliant', true)->count();
            $stayCount = $stays->count();

            $rows[] = [
                'depot' => $depot->depot_name.' ('.$depot->depot_code.')',
                'crews' => number_format($crews),
                'admissions' => number_format($admissions),
                'rest_compliance' => $this->pct($compliant, $stayCount),
                'open_matters' => number_format($openMatters),
                'resolution' => $this->pct($resolvedMatters, $totalMatters),
            ];

            $totals['crews'] += $crews;
            $totals['admissions'] += $admissions;
            $totals['stays'] += $stayCount;
            $totals['compliant'] += $compliant;
            $totals['open'] += $openMatters;
            $totals['resolved'] += $resolvedMatters;
        }

        $sections = [];
        if ($rows === []) {
            $sections[] = ['title' => 'No data', 'note' => 'No depots have activity in the selected period.', 'columns' => [], 'rows' => []];
        } else {
            $sections[] = ['title' => 'Depot comparison', 'note' => 'Rest compliance compares completed stays in '.$month.' against the required rest (12h at home depot, 10h away).', 'columns' => ['Depot', 'Crews', 'Admissions', 'Rest compliance', 'Open matters', 'Resolution rate'], 'rows' => $rows];
        }

        return [
            'kpis' => [
                ['label' => 'Active crews', 'value' => number_format($totals['crews'])],
                ['label' => 'Admissions', 'value' => number_format($totals['admissions'])],
                ['label' => 'Rest compliance', 'value' => $this->pct($totals['compliant'], $totals['stays'])],
                ['label' => 'Open matters', 'value' => number_format($totals['open']), 'sub' => 'Resolved '.number_format($totals['resolved'])],
            ],
            'sections' => $sections,
            'filters_applied' => ['Month' => $month, 'Depot' => $depotFilter ?? 'All'],
            'as_of' => date('Y-m-d H:i'),
        ];
    }
}