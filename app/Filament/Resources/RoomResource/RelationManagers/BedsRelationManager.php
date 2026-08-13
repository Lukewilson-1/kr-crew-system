<?php

namespace App\Filament\Resources\RoomResource\RelationManagers;

use App\Models\RoomBed;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BedsRelationManager extends RelationManager
{
    protected static string $relationship = 'roomBeds';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bed_no')
                    ->label('Bed')
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold'),
                IconColumn::make('is_usable')
                    ->label('Usable')
                    ->boolean(),
                TextColumn::make('state')
                    ->label('Status')
                    ->state(fn (RoomBed $record) => $record->isOccupied() ? 'Occupied' : ($record->is_usable ? 'Free' : 'Unusable'))
                    ->badge()
                    ->color(fn (RoomBed $record) => $record->isOccupied() ? 'danger' : ($record->is_usable ? 'success' : 'gray')),
            ])
            ->defaultSort('bed_no')
            ->headerActions([
                CreateAction::make()
                    ->form([
                        TextInput::make('bed_no')
                            ->label('Bed number')
                            ->required()
                            ->maxLength(16),
                        Toggle::make('is_usable')
                            ->label('Usable')
                            ->default(true),
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        TextInput::make('bed_no')
                            ->label('Bed number')
                            ->required()
                            ->maxLength(16),
                        Toggle::make('is_usable')
                            ->label('Usable')
                            ->default(true),
                    ]),
                DeleteAction::make()
                    ->hidden(fn (RoomBed $record) => $record->isOccupied())
                    ->tooltip(fn (RoomBed $record) => $record->isOccupied() ? 'Check the guest out before removing this bed.' : null),
            ]);
    }
}
