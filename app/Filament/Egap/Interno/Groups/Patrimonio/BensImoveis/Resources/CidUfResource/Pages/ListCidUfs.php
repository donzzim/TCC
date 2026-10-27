<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CidUfResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CidUfResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Resources\Pages\ListRecords;

class ListCidUfs extends ListRecords
{
    protected static string $resource = CidUfResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
