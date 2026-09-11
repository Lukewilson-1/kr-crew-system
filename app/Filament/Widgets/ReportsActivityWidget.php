<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class ReportsActivityWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.reports-activity-widget';

    protected int | string | array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->isGlobalAccess();
    }

    protected function getViewData(): array
    {
        $reports = [];

        if (DB::getSchemaBuilder()->hasTable('reports')) {
            $reports = DB::table('reports')->orderByDesc('created_at')->limit(5)->get(['name', 'type'])->map(function ($r) {
                return ['name' => $r->name, 'type' => $r->type ?? 'Report'];
            })->toArray();
        }

        return [
            'reports' => $reports,
            'reportCount' => count($reports),
        ];
    }
}
