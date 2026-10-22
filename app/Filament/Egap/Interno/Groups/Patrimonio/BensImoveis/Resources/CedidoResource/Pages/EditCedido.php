<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CedidoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CedidoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCedido extends EditRecord
{
    protected static string $resource = CedidoResource::class;

    protected ?string $heading = 'Editar Imóveis Ocupados por Terceiros';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Excluir'),
        ];
    }
}
