<?php

namespace App\Filament\Pages;

use App\Models\Room;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use UnitEnum;
use BackedEnum;

class MonthlyReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Monthly Report';

    protected static UnitEnum|string|null $navigationGroup = 'Running Rooms';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.pages.monthly-report';

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

    public function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print summary')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action('printSummary'),
        ];
    }

    public function printSummary(): void
    {
        $this->dispatch('kr-print-report');
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
            'matters' => $matters,
            'arrivals_count' => $arrivals->count(),
            'departures_count' => $departures->count(),
            'matters_total' => $matters->count(),
            'matters_open' => $matters->where('status', 'open')->count(),
            'matters_resolved' => $matters->where('status', 'resolved')->count(),
            'avg_stay_hours' => $this->computeAvgStayHours($arrivals),
            'beds' => $room->beds,
            'occupancy_rate' => $this->computeOccupancyRate($room, $start, $end),
            'designation_breakdown' => $arrivals->groupBy('designation')
                ->map(fn ($rows) => $rows->count())
                ->sortDesc()
                ->take(6),
            'weekly_breakdown' => $this->computeWeeklyBreakdown($arrivals, $departures, $start, $end),
            'peak_day' => $arrivals->groupBy(fn ($r) => $r->arrival_date->format('D'))->map->count()->sort()->last() ?? '—',
        ];
    }

    protected function computeAvgStayHours($arrivals): float
    {
        $departed = $arrivals->filter(fn ($r) => $r->departure_date && $r->departure_time && $r->arrival_time);

        if ($departed->isEmpty()) {
            return 0;
        }

        $totalHours = $departed->sum(function ($r) {
            $arrivalTs = strtotime($r->arrival_date->toDateString() . ' ' . $r->arrival_time);
            $departureTs = strtotime($r->departure_date->toDateString() . ' ' . $r->departure_time);

            return max(0, (int) (($departureTs - $arrivalTs) / 3600));
        });

        return round($totalHours / $departed->count(), 1);
    }

    protected function computeOccupancyRate(Room $room, Carbon $start, Carbon $end): int
    {
        $beds = $room->beds;

        if ($beds < 1) {
            return 0;
        }

        $daysInMonth = $start->daysInMonth;
        $bedNights = $beds * $daysInMonth;

        $allRecords = $room->attendanceRecords()
            ->where('arrival_date', '<=', $end->toDateString())
            ->where(function ($q) use ($end) {
                $q->whereNull('departure_date')
                  ->orWhere('departure_date', '>=', $end->copy()->subMonth()->toDateString());
            })
            ->get();

        $endTs = $end->timestamp;
        $occupiedNights = 0;
        $current = $start->copy()->startOfDay();

        while ($current->lte($end)) {
            $dayTs = $current->timestamp;
            $count = $allRecords->filter(function ($r) use ($dayTs, $endTs) {
                $arrivalTs = $r->arrival_date->timestamp;
                if ($arrivalTs > $dayTs) {
                    return false;
                }
                $departureTs = ($r->status === 'out' && $r->departure_date)
                    ? $r->departure_date->timestamp
                    : $endTs;
                return $dayTs <= $departureTs;
            })->count();
            $occupiedNights += min($count, $beds);
            $current->addDay();
        }

        return $bedNights > 0 ? round(($occupiedNights / $bedNights) * 100) : 0;
    }

    protected function computeWeeklyBreakdown($arrivals, $departures, Carbon $start, Carbon $end): array
    {
        $weeks = [];
        $current = $start->copy()->startOfWeek();
        $weekNum = 1;

        while ($current->lte($end)) {
            $weekEnd = (clone $current)->endOfWeek()->min($end);
            $weekLabel = 'Wk ' . $weekNum;
            $weekStartTs = $current->timestamp;
            $weekEndTs = $weekEnd->timestamp;

            $weekArrivals = $arrivals->filter(fn ($r) =>
                $r->arrival_date->timestamp >= $weekStartTs && $r->arrival_date->timestamp <= $weekEndTs
            )->count();

            $weekDepartures = $departures->filter(fn ($r) =>
                $r->departure_date && $r->departure_date->timestamp >= $weekStartTs && $r->departure_date->timestamp <= $weekEndTs
            )->count();

            $weeks[] = [
                'label' => $weekLabel,
                'dates' => $current->format('d') . '–' . $weekEnd->format('d M'),
                'arrivals' => $weekArrivals,
                'departures' => $weekDepartures,
            ];

            $current = $weekEnd->copy()->addDay();
            $weekNum++;
        }

        return $weeks;
    }
}
