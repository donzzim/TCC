<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ObraResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ObraResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Resources\Pages\ListRecords;

class ListObras extends ListRecords
{
    protected static string $resource = ObraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
