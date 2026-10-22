<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\TipoMovimentacaoNotaFiscalResource\Pages;

use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\TipoMovimentacaoNotaFiscalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTipoMovimentacaoNotaFiscals extends ListRecords
{
    protected static string $resource = TipoMovimentacaoNotaFiscalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
        ];
    }
}
