<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Resources\TipoProcessoResource\Pages;

use App\Filament\Egap\Interno\Groups\Processo\Resources\TipoProcessoResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Resources\Pages\ListRecords;

class ListTipoProcessos extends ListRecords
{
    protected static string $resource = TipoProcessoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
