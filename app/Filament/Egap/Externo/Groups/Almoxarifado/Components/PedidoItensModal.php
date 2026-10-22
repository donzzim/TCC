<?php

namespace App\Filament\Egap\Externo\Groups\Almoxarifado\Components;

use App\Filament\Egap\Externo\Utils\PedidoItensModal as PedidoItensModalBase;

/**
 * Itens de um pedido de materiais de consumo, exibidos no modal aberto a
 * partir de {@see PedidosAlmoxarifadoTable} (legado: modal_pedidos.api.php,
 * ramo do setor responsável = Almoxarifado).
 */
class PedidoItensModal extends PedidoItensModalBase
{
    protected function statusColor(int $status): string
    {
        return match ($status) {
            3, 7 => 'success',
            4 => 'gray',
            5 => 'danger',
            6 => 'warning',
            default => 'gray',
        };
    }
}
