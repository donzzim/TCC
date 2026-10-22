<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources\ContaContabilResource\Pages;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\ContaContabilResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContaContabils extends ListRecords
{
    protected static string $resource = ContaContabilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
