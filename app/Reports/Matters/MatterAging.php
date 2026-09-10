<?php

namespace App\Reports\Matters;

use App\Models\Matter;
use App\Models\Room;
use App\Reports\BaseReport;
use App\User;
use Illuminate\Http\Request;

class MatterAging extends BaseReport
{
    public function slug(): string
    {
        return 'matter-aging';
    }

    public function title(): string
    {
        return 'Matters Aging';
    }

    public function icon(): string
    {
        return '🕰️';
    }

    public function category(): string
    {
        return 'Matters Arising';
    }

    public function description(): string
    {
        return 'Open matters grouped by how long they have been unresolved, plus the oldest items needing attention.';
    }

    public function allows(User $user): bool
    {
        return $user->is_active;
    }

    public function filters(): array
    {
        return [
            ['key' => 'as_of', 'label' => 'As of', 'type' => 'date', 'default' => date('Y-m-d')],
            ['key' => 'room', 'label' => 'Room', 'type' => 'select', 'default' => ''],
        ];
    }

    public function generate(Request $request, User $user, array $filterValues): array
    {
        $asOf = $filterValues['as_of'] ?? date('Y-m-d');
        $roomFilter = ($filterValues['room'] ?? '') !== '' ? (int) $filterValues['room'] : null;

        $query = Matter::query()
            ->whereIn('room_id', $this->visibleRoomIds($user))
            ->where('status', 'open')
            ->when($roomFilter, fn ($q) => $q->where('room_id', $roomFilter))
            ->get(['id', 'ticket_no', 'room_id', 'date', 'category', 'description', 'reported_by']);

        $buckets = [
            '0 – 3 days' => 0,
            '4 – 7 days' => 0,
            '8 – 14 days' => 0,
            '15 – 30 days' => 0,
            '31+ days' => 0,
        ];

        $ageList = [];
        foreach ($query as $matter) {
            $days = max(0, (int) floor((strtotime($asOf) - strtotime($matter->date)) / 86400));
            $ageList[$matter->id] = $days;

            if ($days <= 3) {
                $buckets['0 – 3 days']++;
            } elseif ($days <= 7) {
                $buckets['4 – 7 days']++;
            } elseif ($days <= 14) {
                $buckets['8 – 14 days']++;
            } elseif ($days <= 30) {
                $buckets['15 – 30 days']++;
            } else {
                $buckets['31+ days']++;
            }
        }

        $bucketRows = [];
        $open = $query->count();
        foreach ($buckets as $label => $count) {
            $bucketRows[] = ['bucket' => $label, 'count' => number_format($count), 'share' => $this->pct($count, $open)];
        }

        $oldest = $query->sortByDesc(function ($matter) use ($ageList) {
            return $ageList[$matter->id];
        })->values()->take(25);

        $oldestRows = [];
        foreach ($oldest as $matter) {
            $oldestRows[] = [
                'ticket' => $matter->ticket_no,
                'room' => Room::find($matter->room_id)?->name ?? '—',
                'category' => $matter->category,
                'reported' => $matter->date,
                'days' => number_format($ageList[$matter->id]),
            ];
        }

        $agingCount = array_sum(array_slice($buckets, 2));

        $sections = [];
        if ($open === 0) {
            $sections[] = ['title' => 'No data', 'note' => 'No open matters as of '.$asOf.'.', 'columns' => [], 'rows' => []];
        } else {
            $sections[] = ['title' => 'Age distribution', 'note' => 'Aging computed against the selected "as of" date.', 'columns' => ['Age bucket', 'Open', 'Share'], 'rows' => $bucketRows];
            $sections[] = ['title' => 'Oldest open matters', 'note' => '25 matters open the longest.', 'columns' => ['Ticket', 'Room', 'Category', 'Reported', 'Days open'], 'rows' => $oldestRows];
        }

        return [
            'kpis' => [
                ['label' => 'Open matters', 'value' => number_format($open)],
                ['label' => 'Aging (>7 days)', 'value' => number_format($agingCount)],
                ['label' => 'Average age', 'value' => $ageList ? $this->dec(array_sum($ageList) / count($ageList), 0).' days' : '—'],
                ['label' => 'Oldest', 'value' => $ageList ? $this->dec(max($ageList), 0).' days' : '—'],
            ],
            'sections' => $sections,
            'filters_applied' => ['As of' => $asOf, 'Room' => $roomFilter ? Room::find($roomFilter)?->name : 'All'],
            'as_of' => date('Y-m-d H:i'),
        ];
    }
}