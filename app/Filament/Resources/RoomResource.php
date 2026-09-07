<?php

namespace App\Filament\Resources;

use App\Models\Depot;
use App\Models\Room;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-library';

    protected static UnitEnum|string|null $navigationGroup  = 'Running Rooms';

    protected static ?string $navigationLabel = 'Rooms';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('manage_running_rooms');
        }

        if (method_exists($user, 'can')) {
            return $user->can('manage_running_rooms');
        }

        return false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Running room')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Select::make('depot_code')
                ->label('Depot')
                ->helperText('Which depot owns this room. Booking/station officers of this depot manage it.')
                ->options(fn () => Depot::query()->orderBy('depot_name')->pluck('depot_name', 'depot_code')->toArray())
                ->searchable()
                ->required(),
            TextInput::make('beds')
                ->label('Bed capacity')
                ->numeric()
                ->minValue(1)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Room')->searchable()->weight('bold'),
                TextColumn::make('depot_code')->label('Depot')->sortable()->searchable(),
                TextColumn::make('beds')->label('Capacity')->numeric()->fontFamily('mono'),
                TextColumn::make('occupied')
                    ->label('Occupied')
                    ->state(fn (Room $record) => $record->occupiedCount())
                    ->fontFamily('mono'),
                TextColumn::make('vacant')
                    ->label('Vacant')
                    ->state(fn (Room $record) => $record->vacantBeds())
                    ->badge()
                    ->color(fn (Room $record) => $record->isFull() ? 'danger' : 'success'),
                TextColumn::make('open_matters')
                    ->label('Open matters')
                    ->state(fn (Room $record) => $record->matters()->where('status', 'open')->count())
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => RoomResource\Pages\ListRooms::route('/'),
            'create' => RoomResource\Pages\CreateRoom::route('/create'),
            'edit' => RoomResource\Pages\EditRoom::route('/{record}/edit'),
        ];
    }
}
