<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Planejamento\Pages;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Planejamento\PlanejamentoResource;
use Filament\Resources\Pages\ListRecords;

class ListPlanejamentos extends ListRecords
{
    protected static string $resource = PlanejamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
