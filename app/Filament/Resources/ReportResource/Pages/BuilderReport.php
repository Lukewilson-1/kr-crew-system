<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Models\ReportDefinition;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use BackedEnum;

class BuilderReport extends Page
{
    use InteractsWithForms;

    protected static string $resource = ReportResource::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Builder';

    protected string $view = 'filament.pages.report-builder';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'name' => 'New report',
            'slug' => Str::slug('new-report'),
            'type' => 'report',
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
                        TextInput::make('name')->required()->label('Report name'),
                        TextInput::make('slug')->required()->label('Slug'),
                        Textarea::make('description')->rows(3)->label('Description'),
                        Select::make('type')->options([
                            'export' => 'Export',
                            'report' => 'Report',
                            'view' => 'View',
                        ])->required(),
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
            'builder_layout' => $data['builder_layout'] ?? 'table',
            'builder_group_by' => $data['builder_group_by'] ?? null,
            'builder_columns' => [],
            'builder_filters' => [],
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
