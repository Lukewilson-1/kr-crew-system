<?php

namespace App\Filament\Resources;

use App\CrewMember;
use App\Filament\Resources\CrewMemberResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class CrewMemberResource extends Resource
{
    protected static ?string $model = CrewMember::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Crew Members';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('display_name')->required(),
            Forms\Components\TextInput::make('staff_number'),
            Forms\Components\TextInput::make('depot_code'),
            Forms\Components\TextInput::make('designation_code'),
            Forms\Components\TextInput::make('employment_status_code'),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')->label('Name')->searchable()->sortable(),
                TextColumn::make('staff_number')->label('Staff No')->searchable(),
                TextColumn::make('depot_code')->label('Depot')->searchable()->sortable(),
                TextColumn::make('designation_code')->label('Designation')->searchable()->sortable(),
                TextColumn::make('employment_status_code')->label('Status')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('is_active')->label('Active')->boolean()->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_active')->options([1 => 'Active', 0 => 'Inactive']),
                SelectFilter::make('employment_status_code')->options([
                    'SB' => 'Stand By',
                    'BK' => 'Booked',
                    'R' => 'Resting',
                    'L' => 'Leave',
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
            'index' => Pages\ListCrewMembers::route('/'),
            'create' => Pages\CreateCrewMember::route('/create'),
            'edit' => Pages\EditCrewMember::route('/{record}/edit'),
        ];
    }
}
