<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class TimelineWidget extends Widget
{
    protected string $view = 'filament.widgets.timeline-widget';

    protected int | string | array $columnSpan = 1;

    protected function getViewData(): array
    {
        $items = [];

        if (DB::getSchemaBuilder()->hasTable('crew_members')) {
            $rows = DB::table('crew_members')->orderByDesc('created_at')->limit(6)->get(['record_id', 'crew_id', 'depot_code', 'display_name', 'created_at']);

            $items = $rows->map(function ($r) {
                $label = $r->display_name ?: $r->crew_id ?: $r->record_id;

                return [
                    'label' => $label,
                    'meta' => $r->depot_code,
                    'time' => $r->created_at,
                ];
            })->toArray();
        }

        return ['items' => $items];
    }
}
