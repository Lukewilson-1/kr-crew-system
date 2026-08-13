<?php

namespace App\Filament\Resources\MatterResource\Pages;

use App\Filament\Resources\MatterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMatter extends EditRecord
{
    protected static string $resource = MatterResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
