<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\PlanoOrcamentario\Pages;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\PlanoOrcamentario\PlanoOrcamentarioResource;
use Filament\Resources\Pages\ListRecords;

class ListPlanosOrcamentarios extends ListRecords
{
    protected static string $resource = PlanoOrcamentarioResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
