<?php

namespace App\Filament\Resources\Portal\CMSSiteSettingsResource\Pages;

use App\Filament\Resources\Portal\CMSSiteSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCMSSiteSettings extends EditRecord
{
    protected static string $resource = CMSSiteSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
