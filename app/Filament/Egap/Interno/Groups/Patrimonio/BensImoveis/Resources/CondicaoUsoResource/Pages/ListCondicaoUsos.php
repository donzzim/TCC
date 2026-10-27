<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CondicaoUsoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CondicaoUsoResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Resources\Pages\ListRecords;

class ListCondicaoUsos extends ListRecords
{
    protected static string $resource = CondicaoUsoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
