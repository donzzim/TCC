<?php

namespace App\Filament\Resources\Administrativo\ResidenciaJuridicaResource\Pages;

use App\Filament\Resources\Administrativo\ResidenciaJuridicaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListResidenciaJuridicas extends ListRecords
{
    protected static string $resource = ResidenciaJuridicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
