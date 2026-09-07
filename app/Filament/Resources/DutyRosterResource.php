<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DutyRosterResource\Pages;
use App\Filament\Resources\DutyRosterResource\RelationManagers\ItemsRelationManager;
use App\Models\DutyRoster;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DutyRosterResource extends Resource
{
    protected static ?string $model = DutyRoster::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Duty Rosters';
    protected static UnitEnum|string|null $navigationGroup = 'Running Rooms';
    protected static ?int $navigationSort = 25;

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
            return $user->hasPermissionTo('manage_duty_rosters');
        }

        if (method_exists($user, 'can')) {
            return $user->can('manage_duty_rosters');
        }

        return false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
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
            Forms\Components\TextInput::make('depot_code')
                ->label('Depot Code')
                ->required()
                ->maxLength(64),
            Forms\Components\DatePicker::make('roster_date')
                ->label('Roster Date')
                ->required(),
            Forms\Components\TextInput::make('period_label')
                ->label('Period Label')
                ->maxLength(255),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'archived' => 'Archived',
                ])
                ->default('draft')
                ->required(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('depot_code')->label('Depot')->sortable()->searchable(),
                TextColumn::make('roster_date')->label('Date')->date()->sortable(),
                TextColumn::make('period_label')->label('Period')->sortable()->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn ($state) => match ($state) {
                        'published' => 'success',
                        'archived' => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('items_count')->label('Items')->counts('items')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'archived' => 'Archived',
                ]),
            ])
            ->defaultSort('roster_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDutyRosters::route('/'),
            'create' => Pages\CreateDutyRoster::route('/create'),
            'edit' => Pages\EditDutyRoster::route('/{record}/edit'),
        ];
    }
}
