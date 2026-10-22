<?php

namespace App\Filament\Resources\Portal\CMSSettingsResource\Pages;

use App\Filament\Resources\Portal\CMSSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCMSSettings extends ListRecords
{
    protected static string $resource = CMSSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
