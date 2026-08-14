<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\CrewDashboard;
use App\Filament\Pages\RunningRoomDashboard;
use App\Filament\Resources\AttendanceRecordResource;
use App\Filament\Resources\CrewMemberResource;
use App\Filament\Resources\DepotResource;
use App\Filament\Resources\MatterResource;
use App\Filament\Resources\ReportResource;
use App\Filament\Resources\RoomResource;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperationsOverviewWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.operations-overview-widget';

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
        $statusCounts = $this->statusCounts();
        $bookedCrew = $statusCounts['BK'] ?? $statusCounts['booked'] ?? 0;
        $standbyCrew = $statusCounts['SB'] ?? $statusCounts['stand_by'] ?? $statusCounts['standby'] ?? 0;

        $roomTotal = $this->tableExists('rooms') ? DB::table('rooms')->count() : 0;
        $bedTotal = $this->tableExists('rooms')
            ? (int) DB::table('rooms')->sum('beds')
            : 0;
        $occupiedBeds = $this->tableExists('attendance_records')
            ? DB::table('attendance_records')->where('status', 'in')->count()
            : 0;
        $vacantBeds = max(0, $bedTotal - $occupiedBeds);
        $openMatters = $this->tableExists('matters')
            ? DB::table('matters')->where('status', 'open')->count()
            : 0;

        return [
            'periodLabel' => now()->format('M Y'),
            'crewStats' => [
                ['label' => 'Active crew', 'value' => number_format($activeCrew), 'hint' => 'of ' . number_format($crewTotal) . ' on roster', 'tone' => 'emerald'],
                ['label' => 'Booked', 'value' => number_format($bookedCrew), 'hint' => 'Current status entries', 'tone' => 'blue'],
                ['label' => 'Standby', 'value' => number_format($standbyCrew), 'hint' => 'Available for allocation', 'tone' => 'amber'],
                ['label' => 'Depots', 'value' => number_format($depotTotal), 'hint' => 'Reporting locations', 'tone' => 'slate'],
            ],
            'roomStats' => [
                ['label' => 'Running rooms', 'value' => number_format($roomTotal), 'hint' => 'Configured rooms', 'tone' => 'blue'],
                ['label' => 'Beds in use', 'value' => number_format($occupiedBeds), 'hint' => 'Guests checked in', 'tone' => 'emerald'],
                ['label' => 'Vacant beds', 'value' => number_format($vacantBeds), 'hint' => 'of ' . number_format($bedTotal) . ' total', 'tone' => 'amber'],
                ['label' => 'Open matters', 'value' => number_format($openMatters), 'hint' => 'Requiring attention', 'tone' => 'red'],
            ],
            'quickActions' => [
                ['label' => 'Crew dashboard', 'href' => CrewDashboard::getUrl(), 'description' => 'Full crew operations'],
                ['label' => 'Running room dashboard', 'href' => RunningRoomDashboard::getUrl(), 'description' => 'Full room operations'],
                ['label' => 'Add crew', 'href' => CrewMemberResource::getUrl('create'), 'description' => 'Create a crew profile'],
                ['label' => 'Check in guest', 'href' => AttendanceRecordResource::getUrl('create'), 'description' => 'Log a room check-in'],
                ['label' => 'Log matter', 'href' => MatterResource::getUrl('create'), 'description' => 'Report an issue'],
                ['label' => 'Reports', 'href' => ReportResource::getUrl('index'), 'description' => 'Open report builder'],
            ],
            'recentCrew' => $this->recentCrew(),
            'recentCheckins' => $this->recentCheckins(),
            'roomRows' => $this->roomRows(),
            'systemCards' => [
                ['label' => 'Roster records', 'value' => number_format($crewTotal), 'meta' => 'Crew profiles'],
                ['label' => 'Depots', 'value' => number_format($depotTotal), 'meta' => 'Configured locations'],
                ['label' => 'Reports', 'value' => number_format($reportTotal), 'meta' => 'Builder entries'],
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

    private function recentCrew(): array
    {
        if (! $this->tableExists('crew_members')) {
            return [];
        }

        return DB::table('crew_members')
            ->orderByDesc('created_at')
            ->limit(4)
            ->get(['display_name', 'staff_number', 'depot_code', 'created_at'])
            ->map(fn (object $row): array => [
                'name' => $row->display_name ?: 'Unnamed crew member',
                'meta' => trim(($row->staff_number ?: 'No staff no.') . ' / ' . ($row->depot_code ?: 'No depot')),
                'time' => $row->created_at,
            ])
            ->toArray();
    }

    private function recentCheckins(): array
    {
        if (! $this->tableExists('attendance_records')) {
            return [];
        }

        return DB::table('attendance_records')
            ->join('rooms', 'rooms.id', '=', 'attendance_records.room_id')
            ->orderByDesc('attendance_records.created_at')
            ->limit(4)
            ->get([
                'attendance_records.name',
                'attendance_records.designation',
                'attendance_records.arrival_date',
                'rooms.name as room_name',
            ])
            ->map(fn (object $row): array => [
                'name' => $row->name ?: 'Unnamed guest',
                'meta' => trim(($row->room_name ?: 'No room') . ' / ' . ($row->designation ?: '')),
                'time' => $row->arrival_date,
            ])
            ->toArray();
    }

    private function roomRows(): array
    {
        if (! $this->tableExists('rooms')) {
            return [];
        }

        return DB::table('rooms')
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name', 'beds'])
            ->map(function (object $row): array {
                $occupied = $this->tableExists('attendance_records')
                    ? DB::table('attendance_records')->where('room_id', $row->id)->where('status', 'in')->count()
                    : 0;
                $vacant = max(0, (int) $row->beds - $occupied);

                return [
                    'name' => $row->name,
                    'beds' => (int) $row->beds,
                    'occupied' => $occupied,
                    'vacant' => $vacant,
                    'percent' => (int) $row->beds > 0 ? round(($occupied / (int) $row->beds) * 100) : 0,
                ];
            })
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
