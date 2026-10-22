<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Reserva\Pages;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Reserva\ReservaResource;
use Filament\Resources\Pages\ListRecords;

class ListReservas extends ListRecords
{
    protected static string $resource = ReservaResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
