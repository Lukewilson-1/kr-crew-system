<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CrewMemberResource;
use App\Filament\Resources\DepotResource;
use App\Filament\Resources\ReportResource;
use App\Filament\Resources\ShiftTemplateResource;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CrewOperationsDashboardWidget extends Widget
{
    protected string $view = 'filament.widgets.crew-operations-dashboard-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getViewData(): array
    {
        $crewTotal = $this->tableExists('crew_members') ? DB::table('crew_members')->count() : 0;
        $activeCrew = $this->tableExists('crew_members') && $this->columnExists('crew_members', 'is_active')
            ? DB::table('crew_members')->where('is_active', true)->count()
            : $crewTotal;
        $depotTotal = $this->tableExists('depots') ? DB::table('depots')->count() : 0;
        $reportTotal = $this->tableExists('reports') ? DB::table('reports')->count() : 0;
        $shiftTemplateTotal = $this->tableExists('shift_templates') ? DB::table('shift_templates')->count() : 0;
        $statusCounts = $this->statusCounts();
        $bookedCrew = $statusCounts['BK'] ?? $statusCounts['booked'] ?? 0;
        $standbyCrew = $statusCounts['SB'] ?? $statusCounts['stand_by'] ?? $statusCounts['standby'] ?? 0;
        $activePercent = $crewTotal > 0 ? round(($activeCrew / $crewTotal) * 100) : 0;

        return [
            'periodLabel' => now()->format('M Y'),
            'heroStats' => [
                ['label' => 'Active crew', 'value' => number_format($activeCrew), 'hint' => $activePercent . '% of roster', 'tone' => 'emerald'],
                ['label' => 'Booked entries', 'value' => number_format($bookedCrew), 'hint' => 'Current status entries', 'tone' => 'blue'],
                ['label' => 'Standby crew', 'value' => number_format($standbyCrew), 'hint' => 'Available for allocation', 'tone' => 'amber'],
                ['label' => 'Reporting depots', 'value' => number_format($depotTotal), 'hint' => 'Configured locations', 'tone' => 'slate'],
            ],
            'quickActions' => [
                ['label' => 'Add Crew', 'href' => CrewMemberResource::getUrl('create'), 'description' => 'Create a crew profile'],
                ['label' => 'Manage Depots', 'href' => DepotResource::getUrl('index'), 'description' => 'Review depot setup'],
                ['label' => 'Shift Templates', 'href' => ShiftTemplateResource::getUrl('index'), 'description' => 'Maintain shifts'],
                ['label' => 'Reports', 'href' => ReportResource::getUrl('index'), 'description' => 'Open report builder'],
            ],
            'statusRows' => $this->statusRows($statusCounts),
            'statusTotal' => array_sum($statusCounts),
            'depotRows' => $this->depotRows(),
            'recentCrew' => $this->recentCrew(),
            'recentReports' => $this->recentReports(),
            'systemCards' => [
                ['label' => 'Roster records', 'value' => number_format($crewTotal), 'meta' => 'Total crew profiles'],
                ['label' => 'Reports configured', 'value' => number_format($reportTotal), 'meta' => 'Builder entries'],
                ['label' => 'Shift templates', 'value' => number_format($shiftTemplateTotal), 'meta' => 'Reusable duty patterns'],
            ],
        ];
    }

    private function statusCounts(): array
    {
        if ($this->tableExists('crew_status_segments')) {
            $column = $this->columnExists('crew_status_segments', 'status_code') ? 'status_code' : 'status';

            return DB::table('crew_status_segments')
                ->select($column, DB::raw('count(*) as total'))
                ->whereNotNull($column)
                ->groupBy($column)
                ->orderByDesc('total')
                ->pluck('total', $column)
                ->map(fn ($value) => (int) $value)
                ->toArray();
        }

        if ($this->tableExists('crew_members') && $this->columnExists('crew_members', 'employment_status_code')) {
            return DB::table('crew_members')
                ->select('employment_status_code', DB::raw('count(*) as total'))
                ->whereNotNull('employment_status_code')
                ->groupBy('employment_status_code')
                ->orderByDesc('total')
                ->pluck('total', 'employment_status_code')
                ->map(fn ($value) => (int) $value)
                ->toArray();
        }

        return [];
    }

    private function statusRows(array $statusCounts): array
    {
        $labels = [
            'BK' => 'Booked',
            'SB' => 'Standby',
            'R' => 'Resting',
            'L' => 'Leave',
            'SK' => 'Sick',
            'T' => 'Training',
            'NTB' => 'Not booked',
            'TO' => 'Trip off',
        ];
        $colors = [
            'BK' => '#16a34a',
            'SB' => '#2563eb',
            'R' => '#7c3aed',
            'L' => '#f97316',
            'SK' => '#dc2626',
            'T' => '#0d9488',
            'NTB' => '#475569',
            'TO' => '#be123c',
        ];
        $total = array_sum($statusCounts) ?: 1;

        return collect($statusCounts)
            ->map(fn (int $count, string $code): array => [
                'code' => $code,
                'label' => $labels[$code] ?? str($code)->replace('_', ' ')->title()->toString(),
                'count' => $count,
                'percent' => round(($count / $total) * 100),
                'color' => $colors[$code] ?? '#64748b',
            ])
            ->sortByDesc('count')
            ->values()
            ->take(7)
            ->toArray();
    }

    private function depotRows(): array
    {
        if (! $this->tableExists('depots')) {
            return [];
        }

        $nameColumn = $this->columnExists('depots', 'depot_name') ? 'depot_name' : 'depot_code';

        if (! $this->tableExists('crew_members')) {
            return DB::table('depots')
                ->select('depot_code', "{$nameColumn} as depot_name")
                ->orderBy($nameColumn)
                ->limit(6)
                ->get()
                ->map(fn (object $row): array => [
                    'code' => $row->depot_code,
                    'name' => $row->depot_name ?: $row->depot_code,
                    'crew' => 0,
                    'active' => 0,
                    'percent' => 0,
                ])
                ->toArray();
        }

        return DB::table('depots')
            ->leftJoin('crew_members', 'depots.depot_code', '=', 'crew_members.depot_code')
            ->select(
                'depots.depot_code',
                "depots.{$nameColumn} as depot_name",
                DB::raw('count(crew_members.record_id) as crew_total'),
                DB::raw('sum(case when crew_members.is_active = 1 then 1 else 0 end) as active_total'),
            )
            ->groupBy('depots.depot_code', "depots.{$nameColumn}")
            ->orderByDesc('crew_total')
            ->limit(6)
            ->get()
            ->map(function (object $row): array {
                $crewTotal = (int) $row->crew_total;
                $activeTotal = (int) $row->active_total;

                return [
                    'code' => $row->depot_code,
                    'name' => $row->depot_name ?: $row->depot_code,
                    'crew' => $crewTotal,
                    'active' => $activeTotal,
                    'percent' => $crewTotal > 0 ? round(($activeTotal / $crewTotal) * 100) : 0,
                ];
            })
            ->toArray();
    }

    private function recentCrew(): array
    {
        if (! $this->tableExists('crew_members')) {
            return [];
        }

        return DB::table('crew_members')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['display_name', 'staff_number', 'depot_code', 'created_at'])
            ->map(fn (object $row): array => [
                'name' => $row->display_name ?: 'Unnamed crew member',
                'meta' => trim(($row->staff_number ?: 'No staff no.') . ' / ' . ($row->depot_code ?: 'No depot')),
                'time' => $row->created_at,
            ])
            ->toArray();
    }

    private function recentReports(): array
    {
        if (! $this->tableExists('reports')) {
            return [];
        }

        return DB::table('reports')
            ->orderByDesc('created_at')
            ->limit(4)
            ->get(['name', 'type', 'category'])
            ->map(fn (object $row): array => [
                'name' => $row->name,
                'type' => $row->type ?: 'Report',
                'category' => $row->category ?: 'General',
            ])
            ->toArray();
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function columnExists(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }
}
