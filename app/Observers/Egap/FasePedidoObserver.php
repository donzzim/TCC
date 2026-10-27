<?php

namespace App\Observers\Egap;

use App\Models\egap\Almoxarifado\FasePedido;
use App\Services\Egap\Pedidos\NotificadorPedido;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class FasePedidoObserver implements ShouldHandleEventsAfterCommit
{
    public function created(FasePedido $fase): void
    {
        if (blank($fase->id_pedido)) {
            return;
        }

        NotificadorPedido::faseRegistrada(
            (int) $fase->id_pedido,
            $fase->idSituacao === null ? null : (int) $fase->idSituacao,
            $fase->Descricao,
        );
    }
}
