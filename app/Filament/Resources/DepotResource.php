<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepotResource\Pages;
use App\Models\Depot;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DepotResource extends Resource
{
    protected static ?string $model = Depot::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Depots';
    protected static UnitEnum|string|null $navigationGroup = 'Crew Management';
    protected static ?int $navigationSort = 20;

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
            return $user->hasPermissionTo('manage_depots');
        }

        if (method_exists($user, 'can')) {
            return $user->can('manage_depots');
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
            Forms\Components\TextInput::make('depot_code')->label('Code')->required()->maxLength(32),
            Forms\Components\TextInput::make('depot_name')->label('Name')->required()->maxLength(255),
            Forms\Components\Select::make('region')
                ->label('Region')
                ->searchable()
                ->options(fn () => \App\Models\Region::query()
                    ->where('is_active', true)
                    ->orderBy('region_name')
                    ->pluck('region_name', 'region_code')
                    ->toArray())
                ->required(),
            Forms\Components\ColorPicker::make('color')->label('Color')->helperText('Choose a depot color for branding and display'),
            Forms\Components\Toggle::make('is_hq')->label('Is HQ')->default(false),
            Forms\Components\Textarea::make('metadata')->label('Metadata (JSON)')->rows(4),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('depot_code')->label('Code')->sortable()->searchable(),
                TextColumn::make('depot_name')->label('Name')->sortable()->searchable(),
                TextColumn::make('region')->label('Region')->sortable()->searchable(),
                TextColumn::make('color')->label('Color')->sortable()->searchable(),
                IconColumn::make('is_hq')->label('HQ')->boolean(),
            ])
            ->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepots::route('/'),
            'create' => Pages\CreateDepot::route('/create'),
            'edit' => Pages\EditDepot::route('/{record}/edit'),
        ];
    }
}
