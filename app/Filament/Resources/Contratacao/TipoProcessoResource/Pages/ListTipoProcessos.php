<?php

namespace App\Filament\Resources\Contratacao\TipoProcessoResource\Pages;

use App\Filament\Resources\Contratacao\TipoProcessoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoProcessos extends ListRecords
{
    protected static string $resource = TipoProcessoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
