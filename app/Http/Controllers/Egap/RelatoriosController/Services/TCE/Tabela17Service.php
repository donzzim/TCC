<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\TCE;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Support\ConsultaEstoqueContaProduto;
use Illuminate\Support\Collection;

/**
 * TCE - Tabela 17: Demonstrativo Analitico das Entradas e Saidas do
 * Almoxarifado - Material Permanente.
 *
 * Legado: relatorios-prints-queries/tce/tabela-17.php
 *
 * Base `conta` (dr.id_tipo_material = 'P'). LEFT (todas com join de NF):
 *   - entradas        : tipo=1, nf.tipo_documento = 1
 *   - entradas_doacao : tipo=1, nf.tipo_documento = 2
 *   - saidas          : tipo=2, nf.tipo_documento = 1
 *   - saidas_doacao   : tipo=2, nf.tipo_documento = 2
 *
 * BUG-LEGADO: filtro de conta / situacao montado e nunca aplicado. Mantido.
 */
final class Tabela17Service implements RelatorioService
{
    use ConsultaEstoqueContaProduto;

    /**
     * @return Collection<int, array{
     *     conta_contabil: ?string, produto: ?string, descricao: ?string,
     *     entradas: float, entradas_doacao: float, saidas: float, saidas_doacao: float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicio = $filtros->inicioHora();
        $termino = $filtros->fimHora();

        $base = $this->tuplasContaProduto('=');
        if ($base->isEmpty()) {
            return collect();
        }

        $entradas = $this->somaEstoquePorContaProduto(1, $inicio, $termino, tipoDocumentoNf: 1);
        $entradasDoacao = $this->somaEstoquePorContaProduto(1, $inicio, $termino, tipoDocumentoNf: 2);
        $saidas = $this->somaEstoquePorContaProduto(2, $inicio, $termino, tipoDocumentoNf: 1);
        $saidasDoacao = $this->somaEstoquePorContaProduto(2, $inicio, $termino, tipoDocumentoNf: 2);

        return $base->map(function ($t) use ($entradas, $entradasDoacao, $saidas, $saidasDoacao): array {
            $chave = $t->id_conta.'|'.$t->id_produto;

            return [
                'conta_contabil' => $t->conta_contabil,
                'produto' => $t->produto,
                'descricao' => $t->descricao,
                'entradas' => (float) ($entradas[$chave]->valor ?? 0),
                'entradas_doacao' => (float) ($entradasDoacao[$chave]->valor ?? 0),
                'saidas' => (float) ($saidas[$chave]->valor ?? 0),
                'saidas_doacao' => (float) ($saidasDoacao[$chave]->valor ?? 0),
            ];
        })->values();
    }
}
