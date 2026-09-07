<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\RunningRoomOperationsWidget;
use Filament\Pages\Page;
use UnitEnum;
use BackedEnum;

class RunningRoomDashboard extends Page
{
    protected string $view = 'filament.pages.running-room-dashboard';

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Running Room Dashboard';

    protected static UnitEnum | string | null $navigationGroup = 'Running Rooms';

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
            return $user->hasPermissionTo('manage_running_rooms');
        }

        return false;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RunningRoomOperationsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }
}
