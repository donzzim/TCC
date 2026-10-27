<?php

namespace App\Observers\Egap;

use App\Models\egap\Almoxarifado\Pedidos;
use App\Services\Egap\Pedidos\NotificadorPedido;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class PedidosObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Pedidos $pedido): void
    {
        NotificadorPedido::pedidoCriado($pedido);
    }

    public function updated(Pedidos $pedido): void
    {
        if (! $pedido->wasChanged('idSituacao') || $pedido->idSituacao === null) {
            return;
        }

        $anterior = $pedido->getPrevious()['idSituacao'] ?? null;

        NotificadorPedido::situacaoAlterada($pedido, $anterior === null ? null : (int) $anterior, (int) $pedido->idSituacao);
    }
}
