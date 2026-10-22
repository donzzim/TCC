<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ObraResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ObraResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListObras extends ListRecords
{
    protected static string $resource = ObraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
                ->modalHeading('Adicionar Obras e Ampliações')
                ->modalWidth('md')
                ->createAnother(false),
        ];
    }
}
