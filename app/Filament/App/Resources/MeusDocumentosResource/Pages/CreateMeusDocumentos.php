<?php

namespace App\Filament\App\Resources\MeusDocumentosResource\Pages;

use App\Filament\App\Resources\MeusDocumentosResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMeusDocumentos extends CreateRecord
{
    protected static string $resource = MeusDocumentosResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
