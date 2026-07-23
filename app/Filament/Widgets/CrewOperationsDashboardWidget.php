<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class CrewOperationsDashboardWidget extends Widget
{
    protected string $view = 'filament.widgets.crew-operations-dashboard-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 10;

    public function getHeading(): string
    {
        return 'Crew operations dashboard';
    }

    public function getDescription(): ?string
    {
        return 'A quick view of utilization, standby activity, and depot performance from the current roster data.';
    }

    public function getViewData(): array
    {
        $rows = DB::table('crew_records')->get();
        $crew = [];

        foreach ($rows as $row) {
            $payload = json_decode((string) ($row->payload ?? '{}'), true);
            if (! is_array($payload)) {
                $payload = [];
            }

            $payload['id'] = $payload['id'] ?? ($row->crew_id ?: $row->record_id);
            $payload['record_id'] = $row->record_id;
            $payload['depot'] = $payload['depot'] ?? $row->depot;
            $payload['name'] = $payload['name'] ?? $row->record_id;
            $payload['status'] = $payload['status'] ?? '';
            $crew[] = $payload;
        }

        $totalCrew = count($crew);
        $statusCounts = array_fill_keys(['BK', 'SB', 'R', 'L', 'SK', 'NTB', 'TO'], 0);

        foreach ($crew as $entry) {
            $code = strtoupper((string) ($entry['status'] ?? ''));
            if (isset($statusCounts[$code])) {
                $statusCounts[$code]++;
            }
        }

        $daysInMonth = now()->daysInMonth;
        $bookedDays = 0;
        $standbyDays = 0;
        $standbyLeaders = [];

        foreach ($crew as $entry) {
            $monthly = is_array($entry['monthly'] ?? null) ? $entry['monthly'] : [];
            $entryBookedDays = 0;
            $entryStandbyDays = 0;

            foreach ($monthly as $key => $value) {
                if (str_starts_with((string) $key, 'd') && $value === 'BK') {
                    $entryBookedDays++;
                }

                if (str_starts_with((string) $key, 'd') && $value === 'SB') {
                    $entryStandbyDays++;
                }
            }

            $bookedDays += $entryBookedDays;
            $standbyDays += $entryStandbyDays;

            if ($entryStandbyDays > 0) {
                $standbyLeaders[] = [
                    'name' => (string) ($entry['name'] ?? $entry['id'] ?? 'Unknown'),
                    'depot' => (string) ($entry['depot'] ?? 'Unassigned'),
                    'standby_days' => $entryStandbyDays,
                ];
            }
        }

        usort($standbyLeaders, fn ($a, $b) => $b['standby_days'] <=> $a['standby_days']);
        $standbyLeaders = array_slice($standbyLeaders, 0, 5);

        $depotBreakdown = [];
        foreach ($crew as $entry) {
            $depot = (string) ($entry['depot'] ?? 'Unassigned');
            if (! isset($depotBreakdown[$depot])) {
                $depotBreakdown[$depot] = [
                    'depot' => $depot,
                    'crew' => 0,
                    'booked' => 0,
                    'standby' => 0,
                ];
            }

            $depotBreakdown[$depot]['crew']++;
            $status = strtoupper((string) ($entry['status'] ?? ''));
            if ($status === 'BK') {
                $depotBreakdown[$depot]['booked']++;
            }
            if ($status === 'SB') {
                $depotBreakdown[$depot]['standby']++;
            }
        }

        $depotBreakdown = array_values($depotBreakdown);
        usort($depotBreakdown, fn ($a, $b) => $b['booked'] <=> $a['booked']);
        $bookedValues = array_column($depotBreakdown, 'booked');
        $maxBooked = ! empty($bookedValues) ? max($bookedValues) : 0;
        $maxBooked = max(1, $maxBooked);

        foreach ($depotBreakdown as &$item) {
            $item['pct'] = $maxBooked ? round(($item['booked'] / $maxBooked) * 100) : 0;
        }
        unset($item);

        $utilizationPercent = $totalCrew > 0 && $daysInMonth > 0
            ? round(($bookedDays / ($daysInMonth * $totalCrew)) * 100, 1)
            : 0;

        return [
            'summary' => [
                ['label' => 'Total crew', 'value' => $totalCrew, 'hint' => 'Records currently available in the roster'],
                ['label' => 'Booked', 'value' => $statusCounts['BK'], 'hint' => 'Crew currently assigned to a train'],
                ['label' => 'Standby', 'value' => $statusCounts['SB'], 'hint' => 'Crew on standby status'],
                ['label' => 'Resting', 'value' => $statusCounts['R'], 'hint' => 'Crew still within rest'],
            ],
            'utilizationPercent' => $utilizationPercent,
            'bookedDays' => $bookedDays,
            'standbyDays' => $standbyDays,
            'depotBreakdown' => $depotBreakdown,
            'standbyLeaders' => $standbyLeaders,
        ];
    }
}
