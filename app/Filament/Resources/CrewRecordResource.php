<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CrewRecordResource\Pages;
use App\CrewRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class CrewRecordResource extends Resource
{
    protected static ?string $model = CrewRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Crew Reports';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('staff_number'),
            Forms\Components\TextInput::make('depot'),
            Forms\Components\TextInput::make('designation'),
            Forms\Components\TextInput::make('status'),
            Forms\Components\TextInput::make('route'),
            Forms\Components\Textarea::make('notes')->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('staff_number')->label('Staff No')->searchable(),
                TextColumn::make('depot')->searchable()->sortable(),
                TextColumn::make('designation')->searchable()->sortable(),
                TextColumn::make('status')->searchable()->sortable(),
                TextColumn::make('route')->limit(30),
                TextColumn::make('created_at')->dateTime()->label('Created')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'SB' => 'Stand By',
                    'BK' => 'Booked',
                    'R' => 'Resting',
                    'L' => 'Leave',
                ]),
                SelectFilter::make('depot')->relationship('depot', 'name'),
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
            'index' => Pages\ListCrewRecords::route('/'),
            'create' => Pages\CreateCrewRecord::route('/create'),
            'edit' => Pages\EditCrewRecord::route('/{record}/edit'),
        ];
    }
}
