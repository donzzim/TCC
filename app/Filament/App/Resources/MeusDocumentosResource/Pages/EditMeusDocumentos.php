<?php

namespace App\Filament\App\Resources\MeusDocumentosResource\Pages;

use App\Filament\App\Resources\MeusDocumentosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMeusDocumentos extends EditRecord
{
    protected static string $resource = MeusDocumentosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
