<?php

namespace App\Filament\Resources\RestLocationResource\Pages;

use App\Filament\Resources\RestLocationResource;
use Filament\Resources\Pages\ListRecords;

class ListRestLocations extends ListRecords
{
    protected static string $resource = RestLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
