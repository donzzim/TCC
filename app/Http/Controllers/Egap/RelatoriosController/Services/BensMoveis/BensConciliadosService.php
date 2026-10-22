<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use Illuminate\Support\Collection;

/**
 * Relatorio "Bens Conciliados".
 *
 * Legado: relatorios-prints-queries/bens-moveis/bens-conciliados.php
 *
 * Base `pat` = combos conta x produto de mat_patrimonio.
 * `tab1` = SUM(ROUND(ValorAquisicao,4)) ValorAtual e SUM(ValordaReavaliacao)
 *          para bens com DatadaReavaliacao no periodo e SituacaoBem NOT IN (8,9),
 *          agrupado por (ContaContabil, Produto).
 * Projecao: Valor = ValorAtual; AcertoContabil = ValordaReavaliacao - ValorAtual.
 */
final class BensConciliadosService implements RelatorioService
{
    /**
     * @return Collection<int, array{
     *     conta_contabil: ?string, produto: ?string, descricao: ?string,
     *     valor: float, valor_reavaliacao: float, acerto_contabil: float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicio = $filtros->inicioHora();
        $termino = $filtros->fimHora();

        $base = $this->tuplasContaProduto();

        $tab1 = BemMovel::query()
            ->whereBetween('DatadaReavaliacao', [$inicio, $termino])
            ->whereNotIn('SituacaoBem', [8, 9])
            ->groupBy('ContaContabil', 'Produto')
            ->select(['ContaContabil as id_conta', 'Produto as id_produto'])
            ->selectRaw('SUM(ROUND(ValorAquisicao, 4)) as valor_atual')
            ->selectRaw('SUM(ValordaReavaliacao) as valor_reavaliacao')
            ->get()
            ->keyBy(fn ($r) => $r->id_conta.'|'.$r->id_produto);

        return $base->map(function ($t) use ($tab1): array {
            $chave = $t->id_conta.'|'.$t->id_produto;
            $valorAtual = (float) ($tab1[$chave]->valor_atual ?? 0);
            $valorReavaliacao = (float) ($tab1[$chave]->valor_reavaliacao ?? 0);

            return [
                'conta_contabil' => $t->conta_contabil,
                'produto' => $t->produto,
                'descricao' => $t->descricao,
                'valor' => $valorAtual,
                'valor_reavaliacao' => $valorReavaliacao,
                'acerto_contabil' => $valorReavaliacao - $valorAtual,
            ];
        })->values();
    }

    /**
     * SELECT DISTINCT pc.id, pr.id, pc.codigo, pr.CodigodaClasse, pr.DescricaodaClasse
     * FROM mat_patrimonio, mat_planocontas, mat_produtos WHERE joins.
     *
     * @return Collection<int, object>
     */
    private function tuplasContaProduto(): Collection
    {
        return BemMovel::query()
            ->from('egap_mat_patrimonio as p')
            ->join('egap_mat_planocontas as pc', 'p.ContaContabil', '=', 'pc.id')
            ->join('egap_mat_produtos as pr', 'p.Produto', '=', 'pr.id')
            ->groupBy('pc.id', 'pr.id', 'pc.codigo', 'pr.CodigodaClasse', 'pr.DescricaodaClasse')
            ->orderBy('pc.codigo')
            ->orderBy('pr.CodigodaClasse')
            ->get([
                'pc.id as id_conta',
                'pr.id as id_produto',
                'pc.codigo as conta_contabil',
                'pr.CodigodaClasse as produto',
                'pr.DescricaodaClasse as descricao',
            ]);
    }
}
