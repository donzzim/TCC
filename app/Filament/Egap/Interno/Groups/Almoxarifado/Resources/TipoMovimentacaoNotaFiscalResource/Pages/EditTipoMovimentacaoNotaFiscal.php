<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\TipoMovimentacaoNotaFiscalResource\Pages;

use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\TipoMovimentacaoNotaFiscalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTipoMovimentacaoNotaFiscal extends EditRecord
{
    protected static string $resource = TipoMovimentacaoNotaFiscalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
