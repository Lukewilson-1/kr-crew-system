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
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Filters\SelectFilter;
use BackedEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\Str;
use UnitEnum;

class ReportResource extends Resource
{
    protected static ?string $model = ReportDefinition::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Reports';
    protected static UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 25;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->label('Report name')
                ->maxLength(255)
                ->reactive()
                ->afterStateUpdated(function ($state, $set, $get) {
                    if (blank($get('slug'))) {
                        $set('slug', Str::slug($state));
                    }
                }),
            TextInput::make('slug')->required()->label('Slug')->maxLength(255),
            Textarea::make('description')->rows(3)->label('Description'),
            TextInput::make('icon')->label('Icon')->maxLength(20),
            Select::make('type')->options([
                'export' => 'Export',
                'report' => 'Report',
                'view' => 'View',
            ])->required(),
            Select::make('report_type')
                ->label('Report type (frontend handler)')
                ->options([
                    'status' => 'Status',
                    'monthly' => 'Monthly',
                    'utilization' => 'Utilization',
                    'absence' => 'Absence / NTB',
                    'print' => 'Print',
                ])
                ->helperText('Determines which handler the crew Reports page uses.')
                ->required(),
            TextInput::make('route_name')->label('Route name')->maxLength(255),
            TextInput::make('action_label')->label('Action label')->maxLength(255),
            TextInput::make('category')->label('Category')->maxLength(255),
            TextInput::make('sort_order')->label('Sort order')->numeric()->default(0),
            Checkbox::make('is_active')->label('Active')->default(true),
            Select::make('builder_layout')
                ->label('Builder layout')
                ->options([
                    'table' => 'Table',
                    'cards' => 'Cards',
                    'timeline' => 'Timeline',
                ])
                ->default('table'),
            Repeater::make('builder_columns')
                ->label('Builder columns')
                ->schema([
                    TextInput::make('label')->required()->label('Label'),
                    Select::make('table')
                        ->label('Table')
                        ->searchable()
                        ->options(fn () => collect(DB::select('SHOW TABLES'))->mapWithKeys(function ($row) {
                            $arr = (array) $row;
                            $name = array_values($arr)[0] ?? null;

                            return $name ? [$name => $name] : [];
                        })->toArray())
                        ->reactive(),
                    Select::make('column')
                        ->label('Column')
                        ->searchable()
                        ->options(fn (callable $get) => ($table = $get('table')) ? array_combine($cols = DbSchema::getColumnListing($table), $cols) : [])
                        ->afterStateUpdated(function ($state, $set, $get) {
                            $table = $get('table');
                            if ($table && $state) {
                                $set('key', $table . '.' . $state);
                            }
                        }),
                    TextInput::make('key')->required()->label('Key'),
                    Select::make('type')->options([
                        'text' => 'Text',
                        'badge' => 'Badge',
                        'date' => 'Date',
                    ])->default('text')->required(),
                ])
                ->collapsible()
                ->default([]),
            Repeater::make('builder_filters')
                ->label('Builder filters')
                ->schema([
                    TextInput::make('label')->required()->label('Label'),
                    Select::make('table')
                        ->label('Table')
                        ->searchable()
                        ->options(fn () => collect(DB::select('SHOW TABLES'))->mapWithKeys(function ($row) {
                            $arr = (array) $row;
                            $name = array_values($arr)[0] ?? null;

                            return $name ? [$name => $name] : [];
                        })->toArray())
                        ->reactive(),
                    Select::make('column')
                        ->label('Column')
                        ->searchable()
                        ->options(fn (callable $get) => ($table = $get('table')) ? array_combine($cols = DbSchema::getColumnListing($table), $cols) : []),
                    TextInput::make('key')->required()->label('Key'),
                    Select::make('type')->options([
                        'select' => 'Select',
                        'date' => 'Date',
                        'text' => 'Text',
                    ])->default('select')->required(),
                ])
                ->collapsible()
                ->default([]),
            TextInput::make('builder_group_by')->label('Group by field'),
        ]);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('manage_reports');
        }

        if (method_exists($user, 'can')) {
            return $user->can('manage_reports');
        }

        return false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(
        \Illuminate\Database\Eloquent\Model $record
    ): bool {
        return static::canViewAny();
    }

    public static function canDelete(
        \Illuminate\Database\Eloquent\Model $record
    ): bool {
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
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
