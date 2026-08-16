<?php

namespace App\Filament\Resources\MatterResource\Pages;

use App\Filament\Resources\MatterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Tonysm\RichTextLaravel\Content;

class EditMatter extends EditRecord
{
    protected static string $resource = MatterResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['description'] = isset($data['description']) && $data['description'] instanceof Content
            ? $data['description']->toHtml()
            : (string) ($data['description'] ?? '');

        return $data;
    }
}
