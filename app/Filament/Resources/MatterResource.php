<?php

namespace App\Filament\Resources;

use App\Models\Matter;
use App\Models\Room;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;
class MatterResource extends Resource
{
    protected static ?string $model = Matter::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Matters Arising';

    protected static UnitEnum|string|null $navigationGroup  = 'Running Rooms';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('room_id')->label('Running room')->options(Room::pluck('name', 'id'))->required(),
            DatePicker::make('date')->required()->default(now()),
            Select::make('category')
                ->options(collect([
                    'Maintenance', 'Cleanliness', 'Security',
                    'Bedding & Supplies', 'Water/Power', 'Staffing', 'Other',
                ])->mapWithKeys(fn ($c) => [$c => $c]))
                ->required(),
            Textarea::make('description')->required()->columnSpanFull(),
            TextInput::make('reported_by'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('room.name')->badge()->color('gray'),
                TextColumn::make('date')->date()->fontFamily('mono'),
                TextColumn::make('category'),
                TextColumn::make('description')->limit(50),
                TextColumn::make('reported_by'),
                BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'open',
                        'success' => 'resolved',
                    ]),
            ])
            ->filters([
                SelectFilter::make('room_id')->label('Room')->options(Room::pluck('name', 'id')),
                SelectFilter::make('status')->options(['open' => 'Open', 'resolved' => 'Resolved']),
            ])
            ->recordActions([
                Action::make('resolve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Matter $record) => $record->status === 'open')
                    ->action(fn (Matter $record) => $record->resolve()),
                Action::make('reopen')
                    ->color('warning')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn (Matter $record) => $record->status === 'resolved')
                    ->action(fn (Matter $record) => $record->reopen()),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => MatterResource\Pages\ListMatters::route('/'),
            'create' => MatterResource\Pages\CreateMatter::route('/create'),
            'edit' => MatterResource\Pages\EditMatter::route('/{record}/edit'),
        ];
    }
}
