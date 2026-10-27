<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\SituacaoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\SituacaoResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Resources\Pages\ListRecords;

class ListSituacaos extends ListRecords
{
    protected static string $resource = SituacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
