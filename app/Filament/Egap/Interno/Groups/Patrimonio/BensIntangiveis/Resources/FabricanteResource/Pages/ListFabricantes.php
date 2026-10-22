<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\FabricanteResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\FabricanteResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFabricantes extends ListRecords
{
    protected static string $resource = FabricanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
