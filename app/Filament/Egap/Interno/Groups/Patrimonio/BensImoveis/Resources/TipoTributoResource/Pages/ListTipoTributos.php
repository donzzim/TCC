<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoTributoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoTributoResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Resources\Pages\ListRecords;

class ListTipoTributos extends ListRecords
{
    protected static string $resource = TipoTributoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
