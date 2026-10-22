<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources\ElementoDespesaResource\Pages;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\ElementoDespesaResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListElementoDespesas extends ListRecords
{
    protected static string $resource = ElementoDespesaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
