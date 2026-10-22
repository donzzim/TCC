<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources\MarcasResource\Pages;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\MarcasResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarcas extends ListRecords
{
    protected static string $resource = MarcasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
