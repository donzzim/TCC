<?php

namespace App\Filament\Resources\Contratacao\PlanoContratacaoResource\Pages;

use App\Filament\Resources\Contratacao\PlanoContratacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanoContratacaos extends ListRecords
{
    protected static string $resource = PlanoContratacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
