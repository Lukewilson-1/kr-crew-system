<?php

namespace App\Reports\Matters;

use App\Models\Matter;
use App\Models\Room;
use App\Reports\BaseReport;
use App\User;
use Illuminate\Http\Request;

class ResolutionSla extends BaseReport
{
    public function slug(): string
    {
        return 'resolution-sla';
    }

    public function title(): string
    {
        return 'Matter Resolution SLA';
    }

    public function icon(): string
    {
        return '✅';
    }

    public function category(): string
    {
        return 'Matters Arising';
    }

    public function description(): string
    {
        return 'How quickly matters are resolved after being raised — average and median days, share resolved within 3 and 7 days.';
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

        $matters = Matter::query()
            ->whereIn('room_id', $this->visibleRoomIds($user))
            ->where('date', '>=', $from)
            ->where('date', '<=', $to)
            ->when($roomFilter, fn ($q) => $q->where('room_id', $roomFilter))
            ->get(['id', 'room_id', 'date', 'category', 'status', 'resolved_date']);

        $byCategory = [];
        $allResolved = [];

        foreach ($matters as $matter) {
            $days = null;
            if ($matter->status === 'resolved' && $matter->resolved_date) {
                $days = max(0, (int) round((strtotime($matter->resolved_date) - strtotime($matter->date)) / 86400));
                $allResolved[] = $days;
            }

            $key = $matter->category ?: 'Uncategorised';
            $byCategory[$key] = $byCategory[$key] ?? ['total' => 0, 'resolved' => 0, 'days' => []];
            $byCategory[$key]['total']++;
            if ($days !== null) {
                $byCategory[$key]['resolved']++;
                $byCategory[$key]['days'][] = $days;
            }
        }

        $rows = [];
        foreach ($byCategory as $category => $stats) {
            $days = $stats['days'];
            sort($days);
            $median = $days ? $days[intdiv(count($days), 2)] : null;
            $within3 = $days ? count(array_filter($days, fn ($d) => $d <= 3)) : 0;
            $within7 = $days ? count(array_filter($days, fn ($d) => $d <= 7)) : 0;

            $rows[] = [
                'category' => $category,
                'total' => number_format($stats['total']),
                'resolved' => number_format($stats['resolved']),
                'open' => number_format($stats['total'] - $stats['resolved']),
                'avg_days' => $days ? $this->dec(array_sum($days) / count($days), 1) : '—',
                'median_days' => $median !== null ? number_format($median) : '—',
                'within_3d' => $days ? $this->pct($within3, count($days)) : '—',
                'within_7d' => $days ? $this->pct($within7, count($days)) : '—',
            ];
        }
        usort($rows, fn ($a, $b) => (float) $b['resolved'] <=> (float) $a['resolved']);

        $byRoom = [];
        foreach ($matters as $matter) {
            if ($matter->status !== 'resolved' || ! $matter->resolved_date) {
                continue;
            }
            $days = max(0, (int) round((strtotime($matter->resolved_date) - strtotime($matter->date)) / 86400));
            $roomName = Room::find($matter->room_id)?->name ?? '—';
            $byRoom[$roomName] = $byRoom[$roomName] ?? [];
            $byRoom[$roomName][] = $days;
        }
        $roomRows = [];
        foreach ($byRoom as $roomName => $days) {
            sort($days);
            $roomRows[] = [
                'room' => $roomName,
                'resolved' => number_format(count($days)),
                'avg_days' => $this->dec(array_sum($days) / count($days), 1),
                'median_days' => number_format($days[intdiv(count($days), 2)]),
                'within_7d' => $this->pct(count(array_filter($days, fn ($d) => $d <= 7)), count($days)),
            ];
        }
        usort($roomRows, fn ($a, $b) => (float) $b['resolved'] <=> (float) $a['resolved']);

        $sections = [];
        if ($matters->isEmpty()) {
            $sections[] = ['title' => 'No data', 'note' => 'No matters reported in the selected window.', 'columns' => [], 'rows' => []];
        } else {
            $sections[] = ['title' => 'By category', 'note' => 'Resolution time counts only resolved matters.', 'columns' => ['Category', 'Reported', 'Resolved', 'Open', 'Avg days', 'Median days', '≤ 3 days', '≤ 7 days'], 'rows' => $rows];
            $sections[] = ['title' => 'By running room', 'note' => 'Average resolution time for resolved matters per room.', 'columns' => ['Room', 'Resolved', 'Avg days', 'Median days', '≤ 7 days'], 'rows' => $roomRows];
        }

        $within7 = $allResolved ? count(array_filter($allResolved, fn ($d) => $d <= 7)) : 0;

        return [
            'kpis' => [
                ['label' => 'Resolved', 'value' => number_format(count($allResolved))],
                ['label' => 'Avg resolution', 'value' => $allResolved ? $this->dec(array_sum($allResolved) / count($allResolved), 1).' days' : '—'],
                ['label' => 'Median', 'value' => $allResolved ? number_format((function ($d) { sort($d); return $d[intdiv(count($d), 2)]; })($allResolved)).' days' : '—'],
                ['label' => 'Within 7 days', 'value' => $this->pct($within7, count($allResolved))],
            ],
            'sections' => $sections,
            'filters_applied' => ['Period' => $from.' → '.$to, 'Room' => $roomFilter ? Room::find($roomFilter)?->name : 'All'],
            'as_of' => date('Y-m-d H:i'),
        ];
    }
}