<?php

namespace App\Filament\Resources\Portal\CMSSectionsResource\Pages;

use App\Filament\Resources\Portal\CMSSectionsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCMSSections extends EditRecord
{
    protected static string $resource = CMSSectionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
