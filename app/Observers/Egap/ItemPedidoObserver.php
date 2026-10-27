<?php

namespace App\Observers\Egap;

use App\Models\egap\Almoxarifado\ItemPedido;
use App\Services\Egap\Pedidos\NotificadorPedido;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ItemPedidoObserver implements ShouldHandleEventsAfterCommit
{
    public function updated(ItemPedido $item): void
    {
        if (! $item->wasChanged(['situacao', 'QuantidadeMaterialAtendida', 'ObservacaoItem'])) {
            return;
        }

        $anterior = $item->getPrevious();
        $situacao = fn ($valor): ?int => $valor === null ? null : (int) $valor;

        NotificadorPedido::itemAlterado(
            $item,
            de: $item->wasChanged('situacao') ? $situacao($anterior['situacao'] ?? null) : $situacao($item->situacao),
            para: $situacao($item->situacao),
            atendidaDe: $item->wasChanged('QuantidadeMaterialAtendida') ? (int) ($anterior['QuantidadeMaterialAtendida'] ?? 0) : null,
            observacao: $item->wasChanged('ObservacaoItem'),
        );
    }
}
