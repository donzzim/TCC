<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Resources\TipoDocumentoResource\Pages;

use App\Filament\Egap\Interno\Groups\Processo\Resources\TipoDocumentoResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Resources\Pages\ListRecords;

class ListTipoDocumentos extends ListRecords
{
    protected static string $resource = TipoDocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
