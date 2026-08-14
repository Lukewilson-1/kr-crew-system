<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DesignationResource\Pages;
use App\Models\Designation;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use UnitEnum;

class DesignationResource extends Resource
{
    protected static ?string $model = Designation::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-briefcase'
        ;

    protected static ?string $navigationLabel = 'Designations';
    protected static UnitEnum|string|null $navigationGroup = 'Crew Management';
    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('designation_code')->required()->maxLength(64),
            Forms\Components\TextInput::make('designation_name')->required()->maxLength(255),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

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
            return $user->hasPermissionTo('manage_crew');
        }

        if (method_exists($user, 'can')) {
            return $user->can('manage_crew');
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

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('designation_code')->label('Code')->sortable()->searchable(),
                TextColumn::make('designation_name')->label('Name')->sortable()->searchable(),
            ])
            ->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDesignations::route('/'),
            'create' => Pages\CreateDesignation::route('/create'),
            'edit' => Pages\EditDesignation::route('/{record}/edit'),
        ];
    }
}
