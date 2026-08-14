<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainTypeResource\Pages;
use App\Models\TrainType;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TrainTypeResource extends Resource
{
    protected static ?string $model = TrainType::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-truck';
    protected static UnitEnum|string|null $navigationGroup = 'Crew Management';
    protected static ?string $navigationLabel = 'Train Types';
    protected static ?int $navigationSort = 70;

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

        if ($user->is_super_admin || $user->is_hq || $user->role_code === 'hq_admin' || $user->depot_code === 'HQ') {
            return true;
        }

        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('manage_rosters');
        }

        if (method_exists($user, 'can')) {
            return $user->can('manage_rosters');
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
            Forms\Components\TextInput::make('train_type_code')->label('Code')->required()->maxLength(32)
                ->unique(ignoreRecord: true)->disabledOn('edit'),
            Forms\Components\TextInput::make('train_type_name')->label('Name')->required()->maxLength(255),
            Forms\Components\TextInput::make('sort_order')->label('Order')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
            Forms\Components\Textarea::make('metadata')->label('Metadata (JSON)')->rows(4),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('train_type_code')->label('Code')->sortable()->searchable(),
                TextColumn::make('train_type_name')->label('Name')->sortable()->searchable(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainTypes::route('/'),
            'create' => Pages\CreateTrainType::route('/create'),
            'edit' => Pages\EditTrainType::route('/{record}/edit'),
        ];
    }
}
