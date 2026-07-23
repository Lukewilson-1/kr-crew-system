<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\ReportDefinition;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class ReportResource extends Resource
{
    protected static ?string $model = ReportDefinition::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 25;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->label('Report name')->maxLength(255),
            TextInput::make('slug')->required()->label('Slug')->maxLength(255),
            Textarea::make('description')->rows(3)->label('Description'),
            TextInput::make('icon')->label('Icon')->maxLength(20),
            Select::make('type')->options([
                'export' => 'Export',
                'report' => 'Report',
                'view' => 'View',
            ])->required(),
            TextInput::make('route_name')->label('Route name')->maxLength(255),
            TextInput::make('action_label')->label('Action label')->maxLength(255),
            TextInput::make('category')->label('Category')->maxLength(255),
            TextInput::make('sort_order')->label('Sort order')->numeric()->default(0),
            Checkbox::make('is_active')->label('Active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('category')->badge()->sortable(),
                TextColumn::make('type')->sortable(),
                TextColumn::make('sort_order')->sortable(),
                TextColumn::make('is_active')
                    ->label('Active')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'export' => 'Export',
                    'report' => 'Report',
                    'view' => 'View',
                ]),
                SelectFilter::make('is_active')->options([
                    '1' => 'Active',
                    '0' => 'Inactive',
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
