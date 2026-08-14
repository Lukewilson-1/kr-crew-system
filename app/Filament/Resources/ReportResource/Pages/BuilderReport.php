<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Models\ReportDefinition;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as DbSchema;
use BackedEnum;
use UnitEnum;

class BuilderReport extends Page
{
    use InteractsWithForms;

    protected static string $resource = ReportResource::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Builder';

    protected static UnitEnum | string | null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 25;

    protected string $view = 'filament.pages.report-builder';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'name' => 'New report',
            'slug' => Str::slug('new-report'),
            'type' => 'report',
            'report_type' => 'builder',
            'builder_layout' => 'table',
            'builder_columns' => [],
            'builder_filters' => [],
            'builder_group_by' => null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Report details')
                    ->schema([
                        TextInput::make('name')->required()->label('Report name')->reactive()->afterStateUpdated(function ($state, $set, $get) {
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug($state));
                            }
                        }),
                        TextInput::make('slug')->required()->label('Slug'),
                        Textarea::make('description')->rows(3)->label('Description'),
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
                                'builder' => 'Builder (custom table)',
                            ])
                            ->default('builder')
                            ->helperText('Determines which handler the crew Reports page uses.')
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Builder configuration')
                    ->schema([
                        Select::make('builder_layout')->label('Layout')->options([
                            'table' => 'Table',
                            'cards' => 'Cards',
                            'timeline' => 'Timeline',
                        ])->required(),
                        TextInput::make('builder_group_by')->label('Group by field'),
                        Repeater::make('builder_columns')
                            ->label('Columns')
                            ->schema([
                                TextInput::make('label')->label('Label')->required(),
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
                                TextInput::make('key')->label('Key')->required(),
                                Select::make('type')
                                    ->label('Type')
                                    ->options([
                                        'text' => 'Text',
                                        'badge' => 'Badge',
                                        'date' => 'Date',
                                    ])
                                    ->default('text')
                                    ->required(),
                            ])
                            ->collapsible()
                            ->default([])
                            ->columns(3),
                        Repeater::make('builder_filters')
                            ->label('Filters')
                            ->schema([
                                TextInput::make('label')->label('Label')->required(),
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
                                TextInput::make('key')->label('Key')->required(),
                                Select::make('type')
                                    ->label('Type')
                                    ->options([
                                        'select' => 'Select',
                                        'date' => 'Date',
                                        'text' => 'Text',
                                    ])
                                    ->default('select')
                                    ->required(),
                            ])
                            ->collapsible()
                            ->default([])
                            ->columns(3),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        ReportDefinition::query()->create([
            'name' => $data['name'] ?? 'Untitled report',
            'slug' => $data['slug'] ?? Str::slug('untitled-report'),
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'report',
            'report_type' => $data['report_type'] ?? 'builder',
            'builder_layout' => $data['builder_layout'] ?? 'table',
            'builder_group_by' => $data['builder_group_by'] ?? null,
            'builder_columns' => $data['builder_columns'] ?? [],
            'builder_filters' => $data['builder_filters'] ?? [],
            'is_active' => true,
        ]);

        $this->notify('success', 'Report builder draft saved.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save report')
                ->action('save'),
        ];
    }
}
