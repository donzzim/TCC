<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\DenominacaoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\DenominacaoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDenominacaos extends ListRecords
{
    protected static string $resource = DenominacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
                ->modalHeading('Adicionar Denominação')
                ->modalWidth('md')
                ->createAnother(false),
        ];
    }
}
