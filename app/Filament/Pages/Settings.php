<?php

namespace App\Filament\Pages;

use App\Models\Room;
use DB;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;
use BackedEnum;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static UnitEnum|string|null $navigationGroup = 'Running Rooms';

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
        return Room::with(['users', 'currentlyIn'])->orderBy('name')->get();
    }

    public function getRoomStats(): array
    {
        $rooms = $this->getRooms();
        $totalBeds = $rooms->sum('beds');
        $totalOccupied = $rooms->sum(fn ($r) => $r->currentlyIn->count());
        $vacancyRate = $totalBeds > 0 ? round((($totalBeds - $totalOccupied) / $totalBeds) * 100) : 100;

        return [
            'total_rooms' => $rooms->count(),
            'total_beds' => $totalBeds,
            'total_occupied' => $totalOccupied,
            'vacancy_rate' => $vacancyRate,
        ];
    }

    public function getDesignations(): array
    {
        if (Schema::hasTable('designations')) {
            return DB::table('designations')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('designation_name')
                ->filter()
                ->values()
                ->all();
        }

        return ['Driver', 'Guard', 'Fireman', 'Inspector', 'Shunter', 'Other'];
    }

    public function getCategories(): array
    {
        $row = DB::table('running_room_options')->where('key', 'categories')->first();

        if ($row && $row->options) {
            $decoded = json_decode($row->options, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return ['Maintenance', 'Cleanliness', 'Security', 'Bedding & Supplies', 'Water-Power', 'Staffing', 'Other'];
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

    public function saveDesignations(array $designations): void
    {
        $names = array_values(array_unique(array_filter(array_map('trim', $designations), fn ($v) => $v !== '')));

        if (empty($names)) {
            Notification::make()->title('At least one designation is required.')->danger()->send();

            return;
        }

        if (Schema::hasTable('designations')) {
            $existing = DB::table('designations')
                ->get()
                ->keyBy(fn ($row) => mb_strtolower(trim((string) $row->designation_name)));

            foreach ($names as $name) {
                $key = mb_strtolower(trim($name));
                if (isset($existing[$key])) {
                    DB::table('designations')
                        ->where('designation_code', $existing[$key]->designation_code)
                        ->update(['is_active' => true]);
                } else {
                    DB::table('designations')->updateOrInsert(
                        ['designation_code' => strtoupper(preg_replace('/[^a-zA-Z0-9]/', '_', $name))],
                        [
                            'designation_name' => $name,
                            'sort_order' => 999,
                            'is_active' => true,
                            'metadata' => json_encode(['restEligible' => true]),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }

        Notification::make()->title('Designations updated.')->success()->send();
    }

    public function saveCategories(array $categories): void
    {
        $values = array_values(array_unique(array_map('trim', $categories)));

        if (empty($values)) {
            Notification::make()->title('At least one category is required.')->danger()->send();

            return;
        }

        DB::table('running_room_options')->updateOrInsert(
            ['key' => 'categories'],
            ['options' => json_encode($values), 'updated_at' => now()]
        );

        Notification::make()->title('Matter categories updated.')->success()->send();
    }
}
