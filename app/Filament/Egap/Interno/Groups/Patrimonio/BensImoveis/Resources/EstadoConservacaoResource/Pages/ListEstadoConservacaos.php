<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\EstadoConservacaoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\EstadoConservacaoResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Resources\Pages\ListRecords;

class ListEstadoConservacaos extends ListRecords
{
    protected static string $resource = EstadoConservacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
