<?php

namespace App\Filament\Widgets;

use App\Models\Depot;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CrewDepotChartWidget extends ChartWidget
{
    protected ?string $heading = 'Crew by Depot';
    protected int | string | array $columnSpan = ['sm' => 1, 'lg' => 2];

    protected function getData(): array
    {
        $byDepot = [];
        $colors = [];

        if (DB::getSchemaBuilder()->hasTable('depots')) {
            $depotNameColumn = DB::getSchemaBuilder()->hasColumn('depots', 'depot_name') ? 'depot_name' : (DB::getSchemaBuilder()->hasColumn('depots', 'name') ? 'name' : 'depot_code');

            $rows = Depot::query()
                ->leftJoin('crew_members', 'depots.depot_code', '=', 'crew_members.depot_code')
                ->select("depots.{$depotNameColumn} as depot_name", DB::raw('count(crew_members.record_id) as total'), 'depots.depot_code')
                ->groupBy("depots.{$depotNameColumn}", 'depots.depot_code')
                ->get();

            foreach ($rows as $row) {
                $label = $row->depot_name ?? $row->depot_code ?? 'Unknown';
                $byDepot[$label] = (int) $row->total;
                // Use depot code or name to generate a deterministic color
                $seed = $row->depot_code ?? $label;
                $colors[] = $this->colorFromString($seed);
            }
        }

        return [
            'labels' => array_keys($byDepot),
            'datasets' => [
                [
                    'label' => 'Crew',
                    'data' => array_values($byDepot),
                    'backgroundColor' => $colors,
                ],
            ],
            'options' => [
                'plugins' => [
                    'tooltip' => [
                        'callbacks' => [
                            'label' => "function(context) { return context.dataset.label + ': ' + context.parsed + ' crew'; }",
                        ],
                    ],
                    'legend' => [
                        'position' => 'bottom',
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
    
    protected function colorFromString(string $str): string
    {
        $hash = substr(md5($str), 0, 6);
        return '#'.$hash;
    }

}
