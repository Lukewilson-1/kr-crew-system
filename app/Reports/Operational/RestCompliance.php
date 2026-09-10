<?php

namespace App\Reports\Operational;

use App\Reports\BaseReport;
use App\User;
use Illuminate\Http\Request;

class RestCompliance extends BaseReport
{
    public function slug(): string
    {
        return 'rest-compliance';
    }

    public function title(): string
    {
        return 'Rest-break Compliance';
    }

    public function icon(): string
    {
        return '😴';
    }

    public function category(): string
    {
        return 'Operations';
    }

    public function description(): string
    {
        return 'How often completed running-room stays met the required rest period: 12 hours at the crew home depot, 10 hours elsewhere.';
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
        $visibleDepots = $this->visibleDepots($user);
        $stays = $this->completedStays($from, $to, null, $visibleDepots);

        if ($depotFilter) {
            $stays = $stays->filter(fn ($row) => ($row['room_depot'] ?? null) === $depotFilter);
        }

        $compliant = $stays->where('compliant', true)->count();
        $nonCompliant = $stays->count() - $compliant;
        $avgs = $stays->pluck('stay_hours')->filter(fn ($v) => $v !== null);

        $byRoom = $stays->groupBy(fn ($row) => $row['room_id']);
        $roomRows = [];
        foreach ($byRoom as $roomStays) {
            $first = $roomStays->first();
            $compliantInRoom = $roomStays->where('compliant', true)->count();
            $hours = $roomStays->pluck('stay_hours')->filter(fn ($v) => $v !== null);
            $roomRows[] = [
                'room' => $first['room_name'],
                'depot' => $first['room_depot'] ?? '—',
                'beds' => number_format((int) ($first['beds'] ?? 0)),
                'stays' => number_format($roomStays->count()),
                'compliant' => number_format($compliantInRoom),
                'rate' => $this->pct($compliantInRoom, $roomStays->count()),
                'avg' => $hours->isNotEmpty() ? $this->dec($hours->avg(), 1) : '—',
            ];
        }
        usort($roomRows, fn ($a, $b) => (float) str_replace(',', '', $b['stays']) <=> (float) str_replace(',', '', $a['stays']));

        $sections = [];
        if ($stays->isEmpty()) {
            $sections[] = ['title' => 'No data', 'note' => 'No completed rest stays in the selected window.', 'columns' => [], 'rows' => []];
        } else {
            $sections[] = ['title' => 'By running room', 'note' => 'A stay is compliant when it meets the full required rest (12h at the crew home depot, 10h away).', 'columns' => ['Room', 'Depot', 'Beds', 'Stays', 'Compliant', 'Rate', 'Avg stay (h)'], 'rows' => $roomRows];
        }

        return [
            'kpis' => [
                ['label' => 'Completed stays', 'value' => number_format($stays->count())],
                ['label' => 'Compliant', 'value' => $this->pct($compliant, $stays->count()), 'sub' => number_format($nonCompliant).' short'],
                ['label' => 'Avg rest', 'value' => $avgs->isNotEmpty() ? $this->dec($avgs->avg(), 1).'h' : '—'],
            ],
            'sections' => $sections,
            'filters_applied' => ['Period' => $from.' → '.$to, 'Depot' => $depotFilter ?? 'All'],
            'as_of' => date('Y-m-d H:i'),
        ];
    }
}