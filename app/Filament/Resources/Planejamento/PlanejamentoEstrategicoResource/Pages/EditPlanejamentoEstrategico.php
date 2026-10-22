<?php

namespace App\Filament\Resources\Planejamento\PlanejamentoEstrategicoResource\Pages;

use App\Filament\Resources\Planejamento\PlanejamentoEstrategicoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanejamentoEstrategico extends EditRecord
{
    protected static string $resource = PlanejamentoEstrategicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
