<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\TCE;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Support\ConsultaEstoqueContaProduto;
use Illuminate\Support\Collection;

/**
 * TCE - Tabela 15: Demonstrativo Analitico das Entradas e Saidas do
 * Almoxarifado - Material de Consumo.
 *
 * Legado: relatorios-prints-queries/tce/tabela-15.php
 *
 * Base `conta` (dr.id_tipo_material <> 'P'). LEFT:
 *   - entradas       : SUM(valor_total) tipo=1, nf.tipo_documento = 1, periodo
 *   - entradasoutras : SUM(valor_total) tipo=1, nf.tipo_documento = 3, periodo
 *   - saidas         : SUM(valor_total) tipo=2, periodo (sem join de NF)
 * As colunas "Doacao/Perdas/Alienacao" ficam zeradas na apresentacao.
 *
 * BUG-LEGADO: filtro de conta montado e nunca aplicado. Mantido.
 */
final class Tabela15Service implements RelatorioService
{
    use ConsultaEstoqueContaProduto;

    /**
     * @return Collection<int, array{
     *     conta_contabil: ?string, produto: ?string, descricao: ?string,
     *     entradas: float, entradas_outras: float, saidas: float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicio = $filtros->inicioHora();
        $termino = $filtros->fimHora();

        $base = $this->tuplasContaProduto('<>');
        if ($base->isEmpty()) {
            return collect();
        }

        $entradas = $this->somaEstoquePorContaProduto(1, $inicio, $termino, tipoDocumentoNf: 1);
        $entradasOutras = $this->somaEstoquePorContaProduto(1, $inicio, $termino, tipoDocumentoNf: 3);
        $saidas = $this->somaEstoquePorContaProduto(2, $inicio, $termino);

        return $base->map(function ($t) use ($entradas, $entradasOutras, $saidas): array {
            $chave = $t->id_conta.'|'.$t->id_produto;

            return [
                'conta_contabil' => $t->conta_contabil,
                'produto' => $t->produto,
                'descricao' => $t->descricao,
                'entradas' => (float) ($entradas[$chave]->valor ?? 0),
                'entradas_outras' => (float) ($entradasOutras[$chave]->valor ?? 0),
                'saidas' => (float) ($saidas[$chave]->valor ?? 0),
            ];
        })->values();
    }
}
