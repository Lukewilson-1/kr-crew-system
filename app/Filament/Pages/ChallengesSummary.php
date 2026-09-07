<?php

namespace App\Filament\Pages;

use App\Models\Matter;
use App\Models\Room;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use UnitEnum;
use BackedEnum;

class ChallengesSummary extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Challenges Summary';

    protected static UnitEnum|string|null $navigationGroup = 'Running Rooms';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.challenges-summary';

    protected ?\Illuminate\Support\Collection $filtered = null;

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

    public array $data = [
        'roomId' => 'all',
        'status' => 'all',
        'category' => 'all',
        'from' => null,
        'to' => null,
    ];

    public function mount(): void
    {
        $user = auth()->user();

        $this->form->fill([
            'roomId' => $user?->isAttendant() ? (string) $user->room_id : 'all',
            'status' => 'all',
            'category' => 'all',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $user = auth()->user();

        return $schema->components([
            Select::make('roomId')
                ->label('Room')
                ->options(['all' => 'All rooms'] + Room::pluck('name', 'id')->all())
                ->disabled((bool) $user?->isAttendant())
                ->live(),
            Select::make('status')
                ->options(['all' => 'All statuses', 'open' => 'Open', 'resolved' => 'Resolved'])
                ->live(),
            Select::make('category')
                ->options(['all' => 'All categories'] + collect([
                    'Maintenance', 'Cleanliness', 'Security',
                    'Bedding & Supplies', 'Water/Power', 'Staffing', 'Other',
                ])->mapWithKeys(fn ($c) => [$c => $c])->all())
                ->live(),
            DatePicker::make('from')->live(),
            DatePicker::make('to')->live(),
        ])->statePath('data');
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

    public function getFiltered()
    {
        if ($this->filtered !== null) {
            return $this->filtered;
        }

        $user = auth()->user();
        $d = $this->data;

        $query = Matter::query()->with('room')->latest('date');

        if ($user?->isAttendant()) {
            $query->where('room_id', $user->room_id);
        } elseif (($d['roomId'] ?? 'all') !== 'all') {
            $query->where('room_id', $d['roomId']);
        }

        if (($d['status'] ?? 'all') !== 'all') {
            $query->where('status', $d['status']);
        }

        if (($d['category'] ?? 'all') !== 'all') {
            $query->where('category', $d['category']);
        }

        if (! empty($d['from'])) {
            $query->whereDate('date', '>=', $d['from']);
        }

        if (! empty($d['to'])) {
            $query->whereDate('date', '<=', $d['to']);
        }

        return $this->filtered = $query->get();
    }

    public function getByRoom()
    {
        return $this->getFiltered()->groupBy(fn ($row) => $row->room?->name ?: 'Unassigned')
            ->map(fn ($rows) => [
                'total' => $rows->count(),
                'open' => $rows->where('status', 'open')->count(),
                'resolved' => $rows->where('status', 'resolved')->count(),
                'rate' => $rows->count() ? round($rows->where('status', 'resolved')->count() / $rows->count() * 100) : 0,
            ]);
    }

    public function getByCategory()
    {
        return $this->getFiltered()->groupBy(fn ($row) => $row->category ?: 'Other')->map->count();
    }

    public function getKpiStats(): array
    {
        $filtered = $this->getFiltered();
        $total = $filtered->count();
        $open = $filtered->where('status', 'open')->count();
        $resolved = $filtered->where('status', 'resolved')->count();
        $resolutionRate = $total > 0 ? round(($resolved / $total) * 100) : 0;
        $avgResolutionDays = $this->computeAvgResolutionDays($filtered);
        $oldestOpenDays = $this->computeOldestOpenDays($filtered);

        return compact('total', 'open', 'resolved', 'resolutionRate', 'avgResolutionDays', 'oldestOpenDays');
    }

    public function getAgeingBreakdown(): array
    {
        $open = $this->getFiltered()->where('status', 'open');
        $nowTs = now()->timestamp;

        $buckets = ['Under 3 days' => 0, '3–7 days' => 0, '7–14 days' => 0, 'Over 14 days' => 0];

        foreach ($open as $matter) {
            if (! $matter->date) {
                continue;
            }
            $age = (int) floor(($nowTs - $matter->date->timestamp) / 86400);

            if ($age < 3) {
                $buckets['Under 3 days']++;
            } elseif ($age < 7) {
                $buckets['3–7 days']++;
            } elseif ($age < 14) {
                $buckets['7–14 days']++;
            } else {
                $buckets['Over 14 days']++;
            }
        }

        return $buckets;
    }

    public function getWeeklyTrend(): array
    {
        $filtered = $this->getFiltered();

        if ($filtered->isEmpty()) {
            return [];
        }

        $withDates = $filtered->filter(fn ($m) => $m->date);
        if ($withDates->isEmpty()) {
            return [];
        }

        $start = $withDates->min(fn ($m) => $m->date->timestamp);
        $start = Carbon::createFromTimestamp($start)->startOfWeek();
        $end = $withDates->max(fn ($m) => $m->date->timestamp);
        $end = Carbon::createFromTimestamp($end)->endOfWeek();

        $trend = [];
        $current = $start->copy();
        $nowTs = now()->timestamp;

        while ($current->lte($end)) {
            $weekEnd = (clone $current)->endOfWeek()->min($end);
            $weekLabel = $current->format('d M') . '–' . $weekEnd->format('d M');
            $weekStartTs = $current->timestamp;
            $weekEndTs = $weekEnd->timestamp;

            $created = $filtered->filter(fn ($m) =>
                $m->date && $m->date->timestamp >= $weekStartTs && $m->date->timestamp <= $weekEndTs
            )->count();

            $resolved = $filtered->filter(fn ($m) =>
                $m->resolved_date && $m->resolved_date->timestamp >= $weekStartTs && $m->resolved_date->timestamp <= $weekEndTs
            )->count();

            $trend[] = compact('weekLabel', 'created', 'resolved');
            $current = $weekEnd->copy()->addDay();
        }

        return $trend;
    }

    protected function computeAvgResolutionDays($filtered): float
    {
        $resolved = $filtered->filter(fn ($m) => $m->status === 'resolved' && $m->resolved_date && $m->date);

        if ($resolved->isEmpty()) {
            return 0;
        }

        $totalDays = $resolved->sum(fn ($m) => (int) floor(($m->resolved_date->timestamp - $m->date->timestamp) / 86400));

        return round($totalDays / $resolved->count(), 1);
    }

    protected function computeOldestOpenDays($filtered): int
    {
        $open = $filtered->where('status', 'open')->filter(fn ($m) => $m->date);

        if ($open->isEmpty()) {
            return 0;
        }

        $oldestTs = $open->min(fn ($m) => $m->date->timestamp);

        return (int) floor((now()->timestamp - $oldestTs) / 86400);
    }
}
