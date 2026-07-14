<?php

namespace App\Filament\Resources\CrewRecordResource\Pages;

use App\Filament\Resources\CrewRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCrewRecords extends ListRecords
{
    protected static string $resource = CrewRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
