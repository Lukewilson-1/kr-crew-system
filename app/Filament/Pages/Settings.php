<?php

namespace App\Filament\Pages;

use App\Models\Room;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;
use BackedEnum;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum  | string | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static UnitEnum | string | null $navigationGroup = 'Running Rooms';

    protected static ?int $navigationSort = 60;

    protected string $view = 'filament.pages.settings';

   public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->is_active && (
            $user->is_super_admin ||
            $user->is_hq ||
            $user->role_code === 'hq_admin' ||
            $user->depot_code === 'HQ'
        );
    }

    public function mount(): void
    {
        $user = auth()->user();

        abort_unless(
            $user &&
            $user->is_active &&
            (
                $user->is_super_admin ||
                $user->is_hq ||
                $user->role_code === 'hq_admin' ||
                $user->depot_code === 'HQ'
            ),
            403
        );
}

    public function getRooms()
    {
        return Room::with('users')->orderBy('name')->get();
    }

    public function saveBeds(int $roomId, int $beds): void
    {
        if ($beds < 1) {
            Notification::make()->title('Enter at least 1 bed.')->danger()->send();

            return;
        }

        $room = Room::findOrFail($roomId);
        $occupied = $room->occupiedCount();
        $room->update(['beds' => $beds]);

        Notification::make()
            ->title("Bed count for {$room->name} set to {$beds}.")
            ->body($occupied > $beds ? "Note: {$occupied} people are currently checked in, above the new capacity." : null)
            ->warning($occupied > $beds)
            ->success($occupied <= $beds)
            ->send();
    }
}
