<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CrewOperationsDashboardWidget;
use Filament\Pages\Page;
use UnitEnum;
use BackedEnum;

class CrewDashboard extends Page
{
    protected string $view = 'filament.pages.crew-dashboard';

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Crew Dashboard';

    protected static UnitEnum | string | null $navigationGroup = 'Crew Management';

    protected static ?int $navigationSort = 1;

    protected function getHeaderWidgets(): array
    {
        return [
            CrewOperationsDashboardWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }
}
