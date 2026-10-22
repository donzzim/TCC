<?php

namespace App\Filament\Resources\Escola\ComunicacaoTipoResource\Pages;

use App\Filament\Resources\Escola\ComunicacaoTipoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditComunicacaoTipo extends EditRecord
{
    protected static string $resource = ComunicacaoTipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
