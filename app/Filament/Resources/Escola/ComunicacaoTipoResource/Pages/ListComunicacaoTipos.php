<?php

namespace App\Filament\Resources\Escola\ComunicacaoTipoResource\Pages;

use App\Filament\Resources\Escola\ComunicacaoTipoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListComunicacaoTipos extends ListRecords
{
    protected static string $resource = ComunicacaoTipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
