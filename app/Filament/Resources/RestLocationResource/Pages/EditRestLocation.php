<?php

namespace App\Filament\Resources\RestLocationResource\Pages;

use App\Filament\Resources\RestLocationResource;
use Filament\Resources\Pages\EditRecord;

class EditRestLocation extends EditRecord
{
    protected static string $resource = RestLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
