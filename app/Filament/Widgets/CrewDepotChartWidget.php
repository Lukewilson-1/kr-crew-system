<?php

namespace App\Filament\Widgets;

use App\Models\Depot;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CrewDepotChartWidget extends ApexChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $chartId = 'crewDepotChart';

    protected int | string | array $columnSpan = ['sm' => 1, 'lg' => 2];

    protected static ?int $sort = 3;

    protected function getOptions(): array
    {
        $byDepot = [];
        $colors = $this->brandPalette();

        if (DB::getSchemaBuilder()->hasTable('depots')) {
            $depotNameColumn = DB::getSchemaBuilder()->hasColumn('depots', 'depot_name')
                ? 'depot_name'
                : (DB::getSchemaBuilder()->hasColumn('depots', 'name') ? 'name' : 'depot_code');

            $rows = Depot::query()
                ->leftJoin('crew_members', 'depots.depot_code', '=', 'crew_members.depot_code')
                ->select("depots.{$depotNameColumn} as depot_name", DB::raw('count(crew_members.record_id) as total'), 'depots.depot_code')
                ->groupBy("depots.{$depotNameColumn}", 'depots.depot_code')
                ->get();

            foreach ($rows as $row) {
                $label = $row->depot_name ?? $row->depot_code ?? 'Unknown';
                $byDepot[$label] = (int) $row->total;
            }
        }

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 300,
                'toolbar' => ['show' => false],
                'sparkline' => ['enabled' => false],
            ],
            'colors' => $colors,
            'labels' => array_keys($byDepot),
            'series' => array_values($byDepot),
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '65%',
                        'labels' => [
                            'show' => true,
                            'name' => [
                                'show' => true,
                                'fontSize' => '14px',
                                'fontWeight' => 700,
                            ],
                            'value' => [
                                'show' => true,
                                'fontSize' => '22px',
                                'fontWeight' => 800,
                                'formatter' => 'function (val) { return val; }',
                            ],
                        ],
                    ],
                ],
            ],
            'stroke' => [
                'width' => 2,
                'colors' => ['#fff'],
            ],
            'legend' => [
                'position' => 'bottom',
                'fontSize' => '12px',
                'fontWeight' => 600,
                'markers' => [
                    'width' => 10,
                    'height' => 10,
                    'radius' => 2,
                ],
                'itemMargin' => [
                    'horizontal' => 10,
                    'vertical' => 4,
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'tooltip' => [
                'y' => [
                    'formatter' => 'function (val) { return val + " crew"; }',
                ],
            ],
            'responsive' => [
                [
                    'breakpoint' => 640,
                    'options' => [
                        'chart' => ['height' => 240],
                        'legend' => ['position' => 'bottom'],
                    ],
                ],
            ],
        ];
    }

    protected function brandPalette(): array
    {
        return [
            '#6C1A23',
            '#F14219',
            '#FEC000',
            '#4A1118',
            '#C23515',
            '#F8C808',
        ];
    }
}
