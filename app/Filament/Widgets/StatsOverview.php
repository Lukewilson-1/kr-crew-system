<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;



class StatsOverview extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $totalCrew = 0;
        $onShift = 0;
        $depotsReporting = 0;

        if (DB::getSchemaBuilder()->hasTable('crew_members')) {
            $totalCrew = DB::table('crew_members')->count();
            $depotsReporting = DB::table('crew_members')->distinct()->count('depot_code');
        }

        if (DB::getSchemaBuilder()->hasTable('crew_status_segments')) {
            $onShift = DB::table('crew_status_segments')
                ->where('status_code', 'on_shift')
                ->orWhere('status', 'on_shift')
                ->count();
        }

        return [
            Stat::make('Total Crew', $totalCrew)
                ->description('Records in roster'),

            Stat::make('Currently On Shift', $onShift)
                ->description('Derived from roster data'),

            Stat::make('Depots Reporting', $depotsReporting)
                ->description('Active depots with records'),
        ];
    }
}
