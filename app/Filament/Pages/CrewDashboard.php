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

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('manage_crew');
        }

        return false;
    }

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
