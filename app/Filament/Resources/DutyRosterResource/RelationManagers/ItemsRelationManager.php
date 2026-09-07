<?php

namespace App\Filament\Resources\DutyRosterResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('crew_id')
                    ->label('Crew ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('crewMember.display_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('shift_code')->label('Shift')->sortable(),
                TextColumn::make('train_type_code')->label('Train Type')->sortable(),
                TextColumn::make('route_code')->label('Route')->sortable(),
                TextColumn::make('rest_location_code')->label('Rest Location')->sortable(),
                TextColumn::make('duty_date')->label('Duty Date')->date()->sortable(),
            ])
            ->defaultSort('duty_date')
            ->headerActions([
                CreateAction::make()
                    ->form([
                        Select::make('crew_record_id')
                            ->label('Crew Member')
                            ->relationship('crewMember', 'display_name')
                            ->searchable()
                            ->required(),
                        TextInput::make('crew_id')
                            ->label('Crew ID'),
                        TextInput::make('shift_code')
                            ->label('Shift Code'),
                        TextInput::make('train_type_code')
                            ->label('Train Type Code'),
                        TextInput::make('route_code')
                            ->label('Route Code'),
                        TextInput::make('rest_location_code')
                            ->label('Rest Location Code'),
                        DatePicker::make('duty_date')
                            ->label('Duty Date')
                            ->required(),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        Select::make('crew_record_id')
                            ->label('Crew Member')
                            ->relationship('crewMember', 'display_name')
                            ->searchable()
                            ->required(),
                        TextInput::make('crew_id')
                            ->label('Crew ID'),
                        TextInput::make('shift_code')
                            ->label('Shift Code'),
                        TextInput::make('train_type_code')
                            ->label('Train Type Code'),
                        TextInput::make('route_code')
                            ->label('Route Code'),
                        TextInput::make('rest_location_code')
                            ->label('Rest Location Code'),
                        DatePicker::make('duty_date')
                            ->label('Duty Date')
                            ->required(),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                    ]),
                DeleteAction::make(),
            ]);
    }
}
