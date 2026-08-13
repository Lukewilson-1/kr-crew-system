<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StatusCodeResource\Pages;
use App\Filament\Resources\StatusCodeResource\RelationManagers;
use App\Models\StatusCode;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\KeyValue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table as FilamentTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StatusCodeResource extends Resource
{
    protected static ?string $model = StatusCode::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static UnitEnum|string|null $navigationGroup = 'Crew Metadata';
    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Card::make()->schema([
                Grid::make(2)->schema([
                    TextInput::make('status_code')
                        ->label('Status Code')
                        ->required()
                        ->unique(StatusCode::class, 'status_code', ignoreRecord: true)
                        ->maxLength(20),
                    TextInput::make('status_label')
                        ->label('Label')
                        ->required()
                        ->maxLength(100),
                ]),
                Grid::make(2)->schema([
                    TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(500),
                    Toggle::make('is_terminal')
                        ->label('Terminal Status')
                        ->helperText('If checked, this status is considered terminal for reporting or workflow flows.'),
                ]),
                KeyValue::make('metadata')
                    ->label('Metadata')
                    ->keyLabel('Key')
                    ->valueLabel('Value')
                    ->helperText('Add extra metadata such as bg, fg, isAbsence, or any custom properties.'),
            ]),
        ]);
    }

    public static function table(FilamentTable $table): FilamentTable
    {
        return $table
            ->columns([
                TextColumn::make('status_code')->label('Code')->sortable()->searchable(),
                TextColumn::make('status_label')->label('Label')->sortable()->searchable(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
                BooleanColumn::make('is_terminal')->label('Terminal')->sortable(),
                TextColumn::make('metadata')->label('Metadata')->formatStateUsing(fn($state) => is_array($state) ? json_encode($state) : $state),
            ])
            ->filters([
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStatusCodes::route('/'),
            'create' => Pages\CreateStatusCode::route('/create'),
            'edit' => Pages\EditStatusCode::route('/{record}/edit'),
        ];
    }
}
