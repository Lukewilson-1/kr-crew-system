<?php

namespace App\Filament\Resources;

use App\Models\Matter;
use App\Models\Room;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
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

    protected static ?int $navigationSort = 30;

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
            TextInput::make('ticket_no')
                ->label('Ticket no.')
                ->readOnly()
                ->visibleOn('edit')
                ->columnSpanFull(),
            Select::make('room_id')->label('Running room')->options(Room::pluck('name', 'id'))->required(),
            DatePicker::make('date')->required()->default(now()),
            Select::make('category')
                ->options(collect([
                    'Maintenance', 'Cleanliness', 'Security',
                    'Bedding & Supplies', 'Water/Power', 'Staffing', 'Other',
                ])->mapWithKeys(fn ($c) => [$c => $c]))
                ->required(),
            RichEditor::make('description')
                ->toolbarButtons([
                    'bold', 'italic', 'bulletList', 'orderedList',
                    'h2', 'h3', 'link',
                ])
                ->required()
                ->columnSpanFull(),
            TextInput::make('reported_by'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticket_no')
                    ->label('Ticket')
                    ->badge()
                    ->color('info')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('room.name')->badge()->color('gray'),
                TextColumn::make('date')->date()->fontFamily('mono'),
                TextColumn::make('category'),
                TextColumn::make('description')
                    ->label('Description')
                    ->formatStateUsing(fn ($state) => Str::limit(strip_tags((string) $state), 50)),
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
