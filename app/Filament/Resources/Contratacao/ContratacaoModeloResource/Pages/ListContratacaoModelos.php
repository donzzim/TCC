<?php

namespace App\Filament\Resources\Contratacao\ContratacaoModeloResource\Pages;

use App\Filament\Resources\Contratacao\ContratacaoModeloResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContratacaoModelos extends ListRecords
{
    protected static string $resource = ContratacaoModeloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
