<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Projeto\Pages;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Projeto\ProjetoResource;
use Filament\Resources\Pages\ListRecords;

class ListProjetos extends ListRecords
{
    protected static string $resource = ProjetoResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
