<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources\DescricaoResumidaResource\Pages;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\DescricaoResumidaResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDescricaoResumidas extends ListRecords
{
    protected static string $resource = DescricaoResumidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
