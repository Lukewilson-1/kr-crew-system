<?php

namespace App\Filament\Pages;

use App\Models\Matter;
use App\Models\Room;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use UnitEnum;
use BackedEnum;

class ChallengesSummary extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Challenges Summary';

    protected static UnitEnum | string | null $navigationGroup = 'Running Rooms';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.challenges-summary';

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

    public function getFiltered()
    {
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

        return $query->get();
    }

    public function getByRoom()
    {
        return $this->getFiltered()->groupBy('room.name')->map(fn ($rows) => [
            'total' => $rows->count(),
            'open' => $rows->where('status', 'open')->count(),
            'resolved' => $rows->where('status', 'resolved')->count(),
            'rate' => $rows->count() ? round($rows->where('status', 'resolved')->count() / $rows->count() * 100) : 0,
        ]);
    }

    public function getByCategory()
    {
        return $this->getFiltered()->groupBy('category')->map->count();
    }
}
