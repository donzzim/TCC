<?php

namespace App\Filament\Resources\Portal\CMSTemplatesResource\Pages;

use App\Filament\Resources\Portal\CMSTemplatesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCMSTemplates extends EditRecord
{
    protected static string $resource = CMSTemplatesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
