<?php

namespace App\Filament\Egap\Interno\Groups\Pedidos\Pages\Requisicao\SituacaoResource\Pages;

use App\Filament\Egap\Interno\Groups\Pedidos\Pages\Requisicao\Situacao;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSituacaoPage extends ListRecords
{
    protected static string $resource = Situacao::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
        ];
    }
}
