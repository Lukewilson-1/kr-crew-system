<?php

namespace App\Filament\Resources;

use App\Models\AttendanceRecord;
use App\Models\Room;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;

class AttendanceRecordResource extends Resource
{
    protected static ?string $model = AttendanceRecord::class;

    protected static BackedEnum  | string | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Check-in / Check-out';

    protected static UnitEnum | string | null  $navigationGroup = 'Running Rooms';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('room_id')
                ->label('Running room')
                ->options(Room::pluck('name', 'id'))
                ->required()
                ->live(),
            TextInput::make('name')->required(),
            TextInput::make('staff_no')->label('Staff No.'),
            Select::make('designation')
                ->options(collect(['Driver', 'Guard', 'Fireman', 'Inspector', 'Shunter', 'Other'])
                    ->mapWithKeys(fn ($d) => [$d => $d]))
                ->required(),
            TextInput::make('bed_no')->label('Bed No.'),
            DatePicker::make('arrival_date')->required()->default(now()),
            TimePicker::make('arrival_time')->required()->default(now()),
            DatePicker::make('departure_date'),
            TimePicker::make('departure_time'),
            Textarea::make('remarks')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('room.name')->label('Room')->badge()->color('gray'),
                TextColumn::make('name')->searchable()->weight('bold'),
                TextColumn::make('designation'),
                TextColumn::make('bed_no')->label('Bed'),
                TextColumn::make('arrival_date')->date()->fontFamily('mono')->label('Arrived'),
                TextColumn::make('departure_date')->date()->fontFamily('mono')->label('Departed')->placeholder('—'),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'in',
                        'warning' => 'out',
                    ]),
            ])
            ->filters([
                SelectFilter::make('room_id')->label('Room')->options(Room::pluck('name', 'id')),
                SelectFilter::make('status')->options(['in' => 'Checked in', 'out' => 'Checked out']),
            ])
            ->recordActions([
                Action::make('checkOut')
                    ->label('Check out')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->visible(fn (AttendanceRecord $record) => $record->status === 'in')
                    ->requiresConfirmation()
                    ->action(fn (AttendanceRecord $record) => $record->checkOut()),
            ])
            ->defaultSort('arrival_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => AttendanceRecordResource\Pages\ListAttendanceRecords::route('/'),
            'create' => AttendanceRecordResource\Pages\CreateAttendanceRecord::route('/create'),
            'edit' => AttendanceRecordResource\Pages\EditAttendanceRecord::route('/{record}/edit'),
        ];
    }
}
