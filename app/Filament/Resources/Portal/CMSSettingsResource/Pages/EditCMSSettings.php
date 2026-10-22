<?php

namespace App\Filament\Resources\Portal\CMSSettingsResource\Pages;

use App\Filament\Resources\Portal\CMSSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCMSSettings extends EditRecord
{
    protected static string $resource = CMSSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
