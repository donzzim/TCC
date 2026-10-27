<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\EntradaSaidaResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\EntradaSaidaResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Resources\Pages\ListRecords;

class ListEntradaSaidas extends ListRecords
{
    protected static string $resource = EntradaSaidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
