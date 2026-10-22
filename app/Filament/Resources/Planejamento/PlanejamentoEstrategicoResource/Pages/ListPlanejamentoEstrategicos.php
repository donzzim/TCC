<?php

namespace App\Filament\Resources\Planejamento\PlanejamentoEstrategicoResource\Pages;

use App\Filament\Resources\Planejamento\PlanejamentoEstrategicoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanejamentoEstrategicos extends ListRecords
{
    protected static string $resource = PlanejamentoEstrategicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
