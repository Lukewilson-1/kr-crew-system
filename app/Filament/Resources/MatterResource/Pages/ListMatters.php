<?php

namespace App\Filament\Resources\MatterResource\Pages;

use App\Filament\Resources\MatterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMatters extends ListRecords
{
    protected static string $resource = MatterResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
