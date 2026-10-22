<?php

namespace App\Filament\Resources\Portal\CMSPagesResource\Pages;

use App\Filament\Resources\Portal\CMSPagesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCMSPages extends EditRecord
{
    protected static string $resource = CMSPagesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
