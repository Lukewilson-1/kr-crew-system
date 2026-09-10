<?php

namespace App\Reports\Matters;

use App\Models\Matter;
use App\Models\Room;
use App\Reports\BaseReport;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecurringIssues extends BaseReport
{
    public function slug(): string
    {
        return 'recurring-issues';
    }

    public function title(): string
    {
        return 'Recurring Issues';
    }

    public function icon(): string
    {
        return '🔁';
    }

    public function category(): string
    {
        return 'Matters Arising';
    }

    public function description(): string
    {
        return 'Matters grouped by room and category to expose problems that keep coming back and where attention is needed first.';
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

        $roomFilter = ($filterValues['room'] ?? '') !== '' ? (int) $filterValues['room'] : null;

        $query = DB::table('matters as m')
            ->join('rooms as r', 'r.id', '=', 'm.room_id')
            ->whereIn('m.room_id', $this->visibleRoomIds($user))
            ->where('m.date', '>=', $from)
            ->where('m.date', '<=', $to)
            ->when($roomFilter, fn ($q) => $q->where('m.room_id', $roomFilter))
            ->groupBy('r.name', 'm.room_id', 'm.category')
            ->select(
                'r.name as room',
                'm.category',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN m.status = 'resolved' THEN 1 ELSE 0 END) as resolved")
            )
            ->orderByDesc('total')
            ->limit(100)
            ->get();

        $rows = [];
        $recurring = 0;
        foreach ($query as $row) {
            $rows[] = [
                'room' => $row->room,
                'category' => $row->category,
                'total' => number_format((int) $row->total),
                'resolved' => number_format((int) $row->resolved),
                'rate' => $this->pct($row->resolved, $row->total),
            ];
            if ((int) $row->total >= 3) {
                $recurring++;
            }
        }

        $sections = [];
        if ($rows === []) {
            $sections[] = ['title' => 'No data', 'note' => 'No matters reported in the selected window.', 'columns' => [], 'rows' => []];
        } else {
            $sections[] = ['title' => 'Room × category', 'note' => 'Pairs of room and category sorted by how often they recur. A total of 3 or more marks a recurring problem.', 'columns' => ['Room', 'Category', 'Count', 'Resolved', 'Resolution rate'], 'rows' => $rows];
        }

        return [
            'kpis' => [
                ['label' => 'Matters reported', 'value' => number_format($query->sum('total'))],
                ['label' => 'Recurring (≥3)', 'value' => number_format($recurring)],
                ['label' => 'Top issue', 'value' => $query->isNotEmpty() ? $query->first()->room.' / '.$query->first()->category : '—'],
            ],
            'sections' => $sections,
            'filters_applied' => ['Period' => $from.' → '.$to, 'Room' => $roomFilter ? Room::find($roomFilter)?->name : 'All'],
            'as_of' => date('Y-m-d H:i'),
        ];
    }
}