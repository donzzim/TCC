<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\DenominacaoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\DenominacaoResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Resources\Pages\ListRecords;

class ListDenominacaos extends ListRecords
{
    protected static string $resource = DenominacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
