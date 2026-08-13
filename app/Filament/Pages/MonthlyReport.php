<?php

namespace App\Filament\Pages;

use App\Models\Room;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use UnitEnum;
use BackedEnum;

class MonthlyReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Monthly Report';

    protected static UnitEnum | string | null  $navigationGroup = 'Running Rooms';

    protected string $view = 'filament.pages.monthly-report';

    /** @var array{roomId: ?string, period: ?string} */
    public array $data = [];

    public function mount(): void
    {
        $user = auth()->user();
        $defaultRoomId = $user?->isAttendant()
            ? $user->room_id
            : Room::query()->orderBy('name')->value('id');

        $this->form->fill([
            'roomId' => (string) $defaultRoomId,
            'period' => now()->format('Y-m'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $user = auth()->user();

        return $schema->components([
            Select::make('roomId')
                ->label('Running room')
                ->options(Room::pluck('name', 'id'))
                ->disabled((bool) $user?->isAttendant())
                ->live(),
            Select::make('period')
                ->label('Month')
                ->options($this->lastTwelveMonths())
                ->live(),
        ])->statePath('data');
    }

    protected function lastTwelveMonths(): array
    {
        return collect(range(0, 11))
            ->mapWithKeys(fn ($i) => [
                now()->subMonths($i)->format('Y-m') => now()->subMonths($i)->format('F Y'),
            ])
            ->all();
    }

    public function getSummary(): array
    {
        $room = Room::find($this->data['roomId'] ?? null);
        $period = $this->data['period'] ?? now()->format('Y-m');

        if (! $room) {
            return [];
        }

        [$year, $month] = explode('-', $period);
        $start = Carbon::createFromDate((int) $year, (int) $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();

        $arrivals = $room->attendanceRecords()->whereBetween('arrival_date', [$start, $end])->get();
        $departures = $room->attendanceRecords()
            ->whereNotNull('departure_date')
            ->whereBetween('departure_date', [$start, $end])
            ->get();

        $matters = $room->matters()->whereBetween('date', [$start, $end])->get();

        return [
            'room' => $room,
            'period_label' => $start->format('F Y'),
            'arrivals' => $arrivals,
            'departures' => $departures,
            'arrivals_count' => $arrivals->count(),
            'departures_count' => $departures->count(),
            'matters_total' => $matters->count(),
            'matters_open' => $matters->where('status', 'open')->count(),
            'matters_resolved' => $matters->where('status', 'resolved')->count(),
        ];
    }
}
