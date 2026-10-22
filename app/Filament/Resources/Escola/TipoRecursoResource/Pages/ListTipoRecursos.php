<?php

namespace App\Filament\Resources\Escola\TipoRecursoResource\Pages;

use App\Filament\Resources\Escola\TipoRecursoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoRecursos extends ListRecords
{
    protected static string $resource = TipoRecursoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
