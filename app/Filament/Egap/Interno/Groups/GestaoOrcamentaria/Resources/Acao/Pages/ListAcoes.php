<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Acao\Pages;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Acao\AcaoResource;
use Filament\Resources\Pages\ListRecords;

class ListAcoes extends ListRecords
{
    protected static string $resource = AcaoResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
