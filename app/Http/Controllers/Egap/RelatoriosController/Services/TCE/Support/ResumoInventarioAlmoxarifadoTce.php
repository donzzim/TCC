<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Support;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use Illuminate\Support\Collection;

/**
 * Base das tabelas TCE 14 e 16 (Resumo do Inventario do Almoxarifado).
 *
 * Legado:
 *   - tabela-14.php  -> Material de Consumo   (dr.id_tipo_material <> 'P')
 *   - tabela-16.php  -> Material Permanente   (dr.id_tipo_material = 'P')
 *
 * Base `conta` = combos conta contabil x produto que tem estoque do tipo.
 * LEFT:
 *   - saldo_anterior : SUM(valor_total_estoque) da ultima mov. antes do inicio
 *   - entradas       : SUM(valor_total) tipo=1 no periodo
 *   - saidas         : SUM(valor_total) tipo=2 no periodo
 * SaldoAtual = SaldoAnterior + Entradas - Saidas.
 *
 * BUG-LEGADO: os filtros de conta / situacao do legado sao montados mas nunca
 * aplicados a esta query. Mantido (sem filtro).
 */
abstract class ResumoInventarioAlmoxarifadoTce implements RelatorioService
{
    use ConsultaEstoqueContaProduto;

    /** '<>' (consumo) ou '=' (permanente). */
    abstract protected function operadorTipoMaterial(): string;

    /**
     * @return Collection<int, array{
     *     conta_contabil: ?string, produto: ?string, descricao: ?string,
     *     saldo_anterior: float, entradas: float, saidas: float, saldo_atual: float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicioData = $filtros->inicioData();
        $inicio = $filtros->inicioHora();
        $termino = $filtros->fimHora();

        $base = $this->tuplasContaProduto($this->operadorTipoMaterial());
        if ($base->isEmpty()) {
            return collect();
        }

        $saldoAnterior = $this->saldoAnterior($inicioData);
        $entradas = $this->somaEstoquePorContaProduto(1, $inicio, $termino);
        $saidas = $this->somaEstoquePorContaProduto(2, $inicio, $termino);

        return $base->map(function ($t) use ($saldoAnterior, $entradas, $saidas): array {
            $chave = $t->id_conta.'|'.$t->id_produto;

            $sa = (float) ($saldoAnterior[$chave]->valor ?? 0);
            $ent = (float) ($entradas[$chave]->valor ?? 0);
            $sai = (float) ($saidas[$chave]->valor ?? 0);

            return [
                'conta_contabil' => $t->conta_contabil,
                'produto' => $t->produto,
                'descricao' => $t->descricao,
                'saldo_anterior' => $sa,
                'entradas' => $ent,
                'saidas' => $sai,
                'saldo_atual' => $sa + $ent - $sai,
            ];
        })->values();
    }

    /**
     * SUM(valor_total_estoque) da ultima movimentacao antes do inicio, por
     * (conta contabil x produto).
     *
     * @return Collection<string, object>  keyBy "id_conta|id_produto"
     */
    protected function saldoAnterior(string $inicioData): Collection
    {
        $idsUltima = MovimentacaoEstoque::query()
            ->where('date_time', '<', $inicioData)
            ->groupBy('material')
            ->selectRaw('MAX(id) as id');

        return MovimentacaoEstoque::query()
            ->from('egap_alm_estoque as est')
            // joinSub em vez de whereIn(subquery): o MySQL materializa o MAX(id) uma vez.
            ->joinSub($idsUltima, 'ult', 'ult.id', '=', 'est.id')
            ->join('egap_mat_descricaodetalhada as dd', 'est.material', '=', 'dd.id')
            ->join('egap_mat_descricaoresumida as dr', 'dd.descricao_resumida', '=', 'dr.id')
            ->groupBy('dr.ContaContabil', 'dr.id_produto')
            ->select(['dr.ContaContabil as id_conta', 'dr.id_produto as id_produto'])
            ->selectRaw('SUM(est.valor_total_estoque) as valor')
            ->get()
            ->keyBy(fn ($r) => $r->id_conta.'|'.$r->id_produto);
    }
}
