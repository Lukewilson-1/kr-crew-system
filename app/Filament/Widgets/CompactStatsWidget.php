<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class CompactStatsWidget extends Widget
{
    protected string $view = 'filament.widgets.compact-stats-widget';

    protected int | string | array $columnSpan = 1;

    protected function getViewData(): array
    {
        $total = 0;
        $depots = 0;

        if (DB::getSchemaBuilder()->hasTable('crew_members')) {
            $total = DB::table('crew_members')->count();
            $depots = DB::table('crew_members')->distinct()->count('depot_code');
        }

        return [
            'items' => [
                ['label' => 'Total Records', 'value' => $total],
                ['label' => 'Depots', 'value' => $depots],
            ],
        ];
    }
}
