<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\BalancoAnuals\Pages;

use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\BalancoAnualResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBalancoAnuals extends ListRecords
{
    protected static string $resource = BalancoAnualResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
