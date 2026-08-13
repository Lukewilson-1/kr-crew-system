<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftTemplateResource\Pages;
use App\Models\ShiftTemplate;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ShiftTemplateResource extends Resource
{
    protected static ?string $model = ShiftTemplate::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clock';
    protected static UnitEnum|string|null $navigationGroup = 'Crew Operations';
    protected static ?string $navigationLabel = 'Shifts';
    protected static ?int $navigationSort = 6;

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
            Forms\Components\TextInput::make('shift_code')->label('Code')->required()->maxLength(32),
            Forms\Components\TextInput::make('shift_name')->label('Name')->required()->maxLength(255),
            Forms\Components\TextInput::make('starts_at')->label('Starts At')->placeholder('HH:MM:SS'),
            Forms\Components\TextInput::make('ends_at')->label('Ends At')->placeholder('HH:MM:SS'),
            Forms\Components\TextInput::make('sort_order')->label('Order')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
            Forms\Components\Textarea::make('metadata')->label('Metadata (JSON)')->rows(4),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('shift_code')->label('Code')->sortable()->searchable(),
                TextColumn::make('shift_name')->label('Name')->sortable()->searchable(),
                TextColumn::make('starts_at')->label('Starts At'),
                TextColumn::make('ends_at')->label('Ends At'),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShiftTemplates::route('/'),
            'create' => Pages\CreateShiftTemplate::route('/create'),
            'edit' => Pages\EditShiftTemplate::route('/{record}/edit'),
        ];
    }
}
