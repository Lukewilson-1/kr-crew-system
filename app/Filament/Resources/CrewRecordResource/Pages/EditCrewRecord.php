<?php

namespace App\Filament\Resources\CrewRecordResource\Pages;

use App\Filament\Resources\CrewRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCrewRecord extends EditRecord
{
    protected static string $resource = CrewRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
