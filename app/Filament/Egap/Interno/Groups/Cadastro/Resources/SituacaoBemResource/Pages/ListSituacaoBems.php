<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources\SituacaoBemResource\Pages;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\SituacaoBemResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSituacaoBems extends ListRecords
{
    protected static string $resource = SituacaoBemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
