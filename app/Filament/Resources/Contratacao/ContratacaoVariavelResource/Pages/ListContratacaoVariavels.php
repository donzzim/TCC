<?php

namespace App\Filament\Resources\Contratacao\ContratacaoVariavelResource\Pages;

use App\Filament\Resources\Contratacao\ContratacaoVariavelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContratacaoVariavels extends ListRecords
{
    protected static string $resource = ContratacaoVariavelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
