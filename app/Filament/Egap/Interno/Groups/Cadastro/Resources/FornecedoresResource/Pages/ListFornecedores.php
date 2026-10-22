<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources\FornecedoresResource\Pages;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\FornecedoresResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFornecedores extends ListRecords
{
    protected static string $resource = FornecedoresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
