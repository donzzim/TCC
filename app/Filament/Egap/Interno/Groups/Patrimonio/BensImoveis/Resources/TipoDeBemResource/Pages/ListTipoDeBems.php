<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoDeBemResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoDeBemResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Resources\Pages\ListRecords;

class ListTipoDeBems extends ListRecords
{
    protected static string $resource = TipoDeBemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
