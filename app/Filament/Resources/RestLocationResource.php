<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestLocationResource\Pages;
use App\Models\RestLocation;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class RestLocationResource extends Resource
{
    protected static ?string $model = RestLocation::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Rest Locations';
    protected static UnitEnum|string|null $navigationGroup = 'Running Rooms';
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
            return $user->hasPermissionTo('manage_rest_locations');
        }

        if (method_exists($user, 'can')) {
            return $user->can('manage_rest_locations');
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
            Forms\Components\TextInput::make('rest_location_code')
                ->label('Code')
                ->required()
                ->maxLength(64)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('rest_location_name')
                ->label('Name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('depot_code')
                ->label('Depot Code')
                ->maxLength(64),
            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('rest_location_code')->label('Code')->sortable()->searchable(),
                TextColumn::make('rest_location_name')->label('Name')->sortable()->searchable(),
                TextColumn::make('depot_code')->label('Depot')->sortable()->searchable(),
                IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')->options([1 => 'Active', 0 => 'Inactive']),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestLocations::route('/'),
            'create' => Pages\CreateRestLocation::route('/create'),
            'edit' => Pages\EditRestLocation::route('/{record}/edit'),
        ];
    }
}
