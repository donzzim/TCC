<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\MovimentacaoEstoqueResource\Pages;

use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\MovimentacaoEstoqueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMovimentacaoEstoques extends ListRecords
{
    protected static string $resource = MovimentacaoEstoqueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
                ->modalHeading(' ')
        ];
    }
}
