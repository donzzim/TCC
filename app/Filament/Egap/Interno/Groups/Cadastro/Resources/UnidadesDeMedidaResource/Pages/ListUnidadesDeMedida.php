<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources\UnidadesDeMedidaResource\Pages;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\UnidadesDeMedidaResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUnidadesDeMedida extends ListRecords
{
    protected static string $resource = UnidadesDeMedidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
