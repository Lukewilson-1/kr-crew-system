<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Role;
use App\User;
use App\Permission;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('username')
                ->label('Username')
                ->required()
                ->maxLength(64)
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(128),

            Forms\Components\TextInput::make('depot_code')
                ->label('Depot')
                ->maxLength(64),

            Forms\Components\Select::make('role_code')
                ->label('Role')
                ->options(Role::all()->pluck('role_name', 'role_code')->toArray())
                ->searchable()
                ->required(),

            Forms\Components\CheckboxList::make('permissions')
                ->label('Direct Permissions')
                ->options(Permission::all()->pluck('permission_name', 'permission_code')->toArray())
                ->columns(2)
                ->helperText('Select direct permissions for this user.'),

            Forms\Components\TextInput::make('password')
                ->label('Password')
                ->password()
                ->revealable()
                ->maxLength(255)
                ->helperText('Leave blank to keep the existing password. You can view or replace it here.')
                ->dehydrated(fn ($state) => filled($state))
                ->dehydrateStateUsing(fn (?string $state) => $state ? Hash::make($state) : null),

            Forms\Components\TextInput::make('pw')
                ->label('Password (raw / legacy)')
                ->password()
                ->revealable()
                ->maxLength(255)
                ->helperText('Optional legacy field. Leave blank to keep the existing stored hash.'),

            Forms\Components\Toggle::make('is_hq')
                ->label('HQ User'),

            Forms\Components\Toggle::make('is_super_admin')
                ->label('Super Admin'),

            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),

            Forms\Components\Textarea::make('metadata')
                ->label('Metadata JSON')
                ->rows(4)
                ->helperText('Optional JSON metadata for custom access flags.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')->label('Username')->sortable()->searchable(),
                TextColumn::make('name')->label('Name')->sortable()->searchable(),
                TextColumn::make('depot_code')->label('Depot')->sortable()->searchable(),
                TextColumn::make('role_code')->label('Role')->sortable()->searchable(),
                IconColumn::make('is_hq')->label('HQ')->boolean(),
                IconColumn::make('is_super_admin')->label('Super Admin')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('role_code')->label('Role')->options(Role::all()->pluck('role_name', 'role_code')->toArray()),
                SelectFilter::make('is_active')->options([1 => 'Active', 0 => 'Inactive']),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
