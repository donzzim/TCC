<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources\DescricaoDetalhadaResource\Pages;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\DescricaoDetalhadaResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDescricaoDetalhadas extends ListRecords
{
    protected static string $resource = DescricaoDetalhadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
