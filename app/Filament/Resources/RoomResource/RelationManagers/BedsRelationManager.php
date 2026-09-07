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

    /** @var array<string, bool>|null */
    protected ?array $occupancyCache = null;

    protected function bedOccupied(RoomBed $record): bool
    {
        $ownerId = $this->getOwnerRecord()->id;

        if ($this->occupancyCache === null) {
            $this->occupancyCache = RoomBed::occupancyMap(
                $ownerId,
                $this->getRelationship()->pluck('bed_no')
            );
        }

        return $this->occupancyCache[$record->bed_no] ?? false;
    }

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
                    ->state(fn (RoomBed $record) => $this->bedOccupied($record) ? 'Occupied' : ($record->is_usable ? 'Free' : 'Unusable'))
                    ->badge()
                    ->color(fn (RoomBed $record) => $this->bedOccupied($record) ? 'danger' : ($record->is_usable ? 'success' : 'gray')),
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
                    ->hidden(fn (RoomBed $record) => $this->bedOccupied($record))
                    ->tooltip(fn (RoomBed $record) => $this->bedOccupied($record) ? 'Check the guest out before removing this bed.' : null),
            ]);
    }
}
