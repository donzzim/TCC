<?php

namespace App\Filament\Resources\Contratacao\ProcessoEventoResource\Pages;

use App\Filament\Resources\Contratacao\ProcessoEventoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Filament\Schemas\Schema;

class ListProcessoEventos extends ListRecords
{
    protected static string $resource = ProcessoEventoResource::class;
    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
