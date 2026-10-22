<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Components;

use App\Filament\Egap\Externo\Utils\PedidoItensModal as PedidoItensModalBase;
use App\Filament\Egap\Support\TableColumns;
use App\Models\egap\Almoxarifado\ItemPedido;
use App\Services\Egap\Mobile\PedidosMobileService;

class PedidoItensModal extends PedidoItensModalBase
{
    protected function colunasExtras(): array
    {
        return [
            TableColumns::text('justificativa', 'Justificativa')
                ->wrap()
                ->formatStateUsing(fn(?string $state): string => ItemPedido::formatarJustificativa($state)),
        ];
    }

    protected function statusColor(int $status): string
    {
        return match ($status) {
            3, 7 => 'success',
            4, 10 => 'gray',
            5 => 'danger',
            6, 9 => 'warning',
            8 => 'info',
            default => 'gray',
        };
    }

}
