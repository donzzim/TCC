<?php

namespace Tests\Feature\Concerns;

use App\Models\egap\Patrimonio\BensMoveis\AtividadeInventario;
use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use App\Models\egap\Patrimonio\BensMoveis\InventarioComissao;
use App\Models\egap\Patrimonio\BensMoveis\InventarioEquipe;

/**
 * egap_inv_atividades, egap_inv_comissao e egap_inv_equipes são MyISAM: não entram
 * na transação do DatabaseTransactions e o que o teste grava nelas fica no banco
 * real (compartilhado com o EMES). Este trait apaga, no fim do teste, as linhas
 * dos inventários criados pelo próprio teste (id maior que o maior id do início).
 */
trait LimpaTabelasMyIsamDoInventario
{
    private int $maiorInventarioAntesDoTeste = 0;

    protected function setUpLimpaTabelasMyIsamDoInventario(): void
    {
        $this->maiorInventarioAntesDoTeste = (int) Inventario::query()->max('id');
    }

    protected function tearDownLimpaTabelasMyIsamDoInventario(): void
    {
        foreach ([AtividadeInventario::class, InventarioComissao::class, InventarioEquipe::class] as $model) {
            $model::query()->where('id_inventario', '>', $this->maiorInventarioAntesDoTeste)->delete();
        }
    }
}
