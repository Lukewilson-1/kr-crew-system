<?php

namespace App\Filament\Resources;

use App\Models\Room;
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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Running room')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('beds')
                ->label('Bed capacity')
                ->numeric()
                ->minValue(1)
                ->required(),
            TextInput::make('password')
                ->label('Attendant sign-in password')
                ->password()
                ->revealable()
                ->dehydrated(fn ($state) => filled($state))
                ->helperText('Leave blank to keep the current password.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Room')->searchable()->weight('bold'),
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
