<?php

namespace App\Filament\Resources\Portal\CMSSiteSettingsResource\Pages;

use App\Filament\Resources\Portal\CMSSiteSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCMSSiteSettings extends ListRecords
{
    protected static string $resource = CMSSiteSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
