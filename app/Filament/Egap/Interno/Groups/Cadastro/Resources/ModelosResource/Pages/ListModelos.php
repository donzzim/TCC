<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources\ModelosResource\Pages;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\ModelosResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListModelos extends ListRecords
{
    protected static string $resource = ModelosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
