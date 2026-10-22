<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources\ComplementoSetorResource\Pages;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\ComplementoSetorResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListComplementoSetors extends ListRecords
{
    protected static string $resource = ComplementoSetorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
