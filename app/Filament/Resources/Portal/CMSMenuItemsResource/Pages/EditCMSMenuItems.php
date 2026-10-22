<?php

namespace App\Filament\Resources\Portal\CMSMenuItemsResource\Pages;

use App\Filament\Resources\Portal\CMSMenuItemsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCMSMenuItems extends EditRecord
{
    protected static string $resource = CMSMenuItemsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
