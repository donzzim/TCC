<?php

namespace App\Filament\Resources\Portal\CMSMenusResource\Pages;

use App\Filament\Resources\Portal\CMSMenusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCMSMenus extends EditRecord
{
    protected static string $resource = CMSMenusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
