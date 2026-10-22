<?php

namespace App\Filament\Resources\Administrativo\AgendaResource\Pages;

use App\Filament\Resources\Administrativo\AgendaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgendas extends ListRecords
{
    protected static string $resource = AgendaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
