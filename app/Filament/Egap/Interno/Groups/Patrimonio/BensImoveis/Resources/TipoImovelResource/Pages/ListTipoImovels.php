<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoImovelResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoImovelResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Resources\Pages\ListRecords;

class ListTipoImovels extends ListRecords
{
    protected static string $resource = TipoImovelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
