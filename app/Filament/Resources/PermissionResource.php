<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Permissions';

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('permission_code')
                ->label('Code')
                ->required()
                ->maxLength(64)
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('permission_name')
                ->label('Name')
                ->required()
                ->maxLength(128),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(3),

            Forms\Components\Toggle::make('is_system')
                ->label('System Permission')
                ->helperText('System permissions are reserved and cannot be removed easily.'),

            Forms\Components\Toggle::make('metadata.active')
                ->label('Active')
                ->default(true)
                ->hint('Inactive permissions are soft-deleted in the normalized table.'),

            Forms\Components\Toggle::make('metadata.canLogin')
                ->label('Login Permission')
                ->helperText('Permission can be used for sign-in access.'),

            Forms\Components\Toggle::make('metadata.isCrewMember')
                ->label('Crew Permission')
                ->helperText('Permission applies to crew member operations.'),

            Forms\Components\Toggle::make('metadata.isUser')
                ->label('User Permission')
                ->helperText('Permission applies to system users.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('permission_code')->label('Code')->sortable()->searchable(),
                TextColumn::make('permission_name')->label('Name')->sortable()->searchable(),
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
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
