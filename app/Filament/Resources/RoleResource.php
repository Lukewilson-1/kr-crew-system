<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Role;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Roles';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('role_code')
                ->label('Code')
                ->required()
                ->maxLength(64)
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('role_name')
                ->label('Name')
                ->required()
                ->maxLength(128),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(3),

            Forms\Components\Toggle::make('is_system')
                ->label('System Role')
                ->helperText('System roles are reserved and cannot be removed easily.'),

            Forms\Components\Toggle::make('metadata.active')
                ->label('Active')
                ->default(true)
                ->hint('Inactive roles are soft-deleted in the normalized table.'),

            Forms\Components\Toggle::make('metadata.canLogin')
                ->label('Login Permission')
                ->helperText('Role can be used for sign-in access.'),

            Forms\Components\Toggle::make('metadata.isCrewMember')
                ->label('Crew Role')
                ->helperText('Role applies to crew member operations.'),

            Forms\Components\Toggle::make('metadata.isUser')
                ->label('User Role')
                ->helperText('Role applies to system users.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('role_code')->label('Code')->sortable()->searchable(),
                TextColumn::make('role_name')->label('Name')->sortable()->searchable(),
                TextColumn::make('description')->limit(40),
                IconColumn::make('is_system')->label('System')->boolean(),
                IconColumn::make('metadata.active')->label('Active')->boolean(),
                IconColumn::make('metadata.canLogin')->label('Login')->boolean(),
                IconColumn::make('metadata.isCrewMember')->label('Crew')->boolean(),
                IconColumn::make('metadata.isUser')->label('User')->boolean(),
            ])
            ->filters([
                SelectFilter::make('is_system')->options([
                    1 => 'System',
                    0 => 'Custom',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
