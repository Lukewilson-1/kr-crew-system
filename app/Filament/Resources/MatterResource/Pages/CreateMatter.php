<?php

namespace App\Filament\Resources\MatterResource\Pages;

use App\Filament\Resources\MatterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMatter extends CreateRecord
{
    protected static string $resource = MatterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'open';

        return $data;
    }
}
