<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\SituacaoNotaFiscalResource\Pages;

use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\SituacaoNotaFiscalResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSituacaoNotaFiscals extends ListRecords
{
    protected static string $resource = SituacaoNotaFiscalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
