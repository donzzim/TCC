<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\SituacaoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\SituacaoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSituacaos extends ListRecords
{
    protected static string $resource = SituacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
                ->modalHeading('Adicionar Situação')
                ->modalWidth('md')
                ->createAnother(false),
        ];
    }
}
