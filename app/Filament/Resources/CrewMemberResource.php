<?php

namespace App\Filament\Resources;

use App\CrewMember;
use App\Filament\Resources\CrewMemberResource\Pages;
use Filament\Forms;
use App\Models\Depot;
use Illuminate\Support\Facades\DB;
use App\Models\Designation;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use BackedEnum;
use UnitEnum;

class CrewMemberResource extends Resource
{
    protected static ?string $model = CrewMember::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static UnitEnum|string|null $navigationGroup = 'Crew Management';

    protected static ?string $navigationLabel = 'Crew Members';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('display_name')->required(),
            Forms\Components\TextInput::make('staff_number'),
            Forms\Components\Select::make('depot_code')
                ->label('Depot')
                ->options(fn (): array => Depot::query()->orderBy('depot_name')->pluck('depot_name', 'depot_code')->toArray())
                ->searchable()
                ->required(false),
            Forms\Components\Select::make('designation_code')
                ->label('Designation')
                ->options(fn (): array => Designation::where('is_active', true)->orderBy('sort_order')->pluck('designation_name', 'designation_code')->toArray())
                ->searchable()
                ->required(false),
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
                IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
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
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
