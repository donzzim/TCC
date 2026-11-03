<?php

namespace App\Jobs\Egap;

use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use App\Services\Egap\Inventario\AbrangenciaInventario;
use App\Services\Egap\Inventario\NotificadorInventario;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Avisa os lotados nos setores de um inventário que ele foi aberto.
 * Despachado com ->afterResponse(): roda depois da resposta, sem depender de worker de fila.
 */
class NotificarAberturaInventario
{
    use Dispatchable;

    public function __construct(public int $inventarioId)
    {
    }

    public function handle(): void
    {
        $inventario = Inventario::query()->find($this->inventarioId);

        // pode ter sido apagado (ou fechado) entre o salvar e o fim da resposta
        if ($inventario === null || ! $inventario->estaAberto()) {
            return;
        }

        // antes era só o botão "Atualizar situação", fácil de esquecer: sem ele os painéis
        // da comissão não enxergam os bens ainda não conferidos do inventário
        AbrangenciaInventario::marcarBensAInventariar($inventario);

        NotificadorInventario::notificarAbertura($inventario);
    }
}
