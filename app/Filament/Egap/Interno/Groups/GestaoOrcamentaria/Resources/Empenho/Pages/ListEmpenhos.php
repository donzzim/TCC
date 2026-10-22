<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Empenho\Pages;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Empenho\EmpenhoResource;
use Filament\Resources\Pages\ListRecords;

class ListEmpenhos extends ListRecords
{
    protected static string $resource = EmpenhoResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
