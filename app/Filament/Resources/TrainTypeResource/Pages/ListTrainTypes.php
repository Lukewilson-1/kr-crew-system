<?php

namespace App\Filament\Resources\TrainTypeResource\Pages;

use App\Filament\Resources\TrainTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrainTypes extends ListRecords
{
    protected static string $resource = TrainTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
