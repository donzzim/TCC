<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Eventos\Pages;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Eventos\EventosResource;
use Filament\Resources\Pages\ListRecords;

class ListEventos extends ListRecords
{
    protected static string $resource = EventosResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
