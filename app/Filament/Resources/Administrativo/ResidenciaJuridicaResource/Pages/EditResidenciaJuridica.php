<?php

namespace App\Filament\Resources\Administrativo\ResidenciaJuridicaResource\Pages;

use App\Filament\Resources\Administrativo\ResidenciaJuridicaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditResidenciaJuridica extends EditRecord
{
    protected static string $resource = ResidenciaJuridicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
