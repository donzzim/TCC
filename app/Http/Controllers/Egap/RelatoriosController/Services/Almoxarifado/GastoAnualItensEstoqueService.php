<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use Illuminate\Support\Collection;

/**
 * Relatorio "Gasto Anual com Itens de Estoque".
 *
 * Legado: relatorios-prints-queries/almoxarifado/almoxarifado-gasto-anual-itens-estoque.php
 *
 *   SELECT CONCAT(pla.CodigodaClasse,' - ',pla.DescricaodaClasse) contabil,
 *          CONCAT(dd.descricao_detalhada,' (',un.Sigla,')') material,
 *          SUM(est.quantidade) qtde, SUM(est.valor_total) valor
 *   FROM alm_estoque est
 *     INNER JOIN mat_descricaodetalhada dd ON est.material = dd.id
 *     INNER JOIN mat_descricaoresumida dr ON dd.descricao_resumida = dr.id
 *     INNER JOIN mat_produtos pla ON dr.id_produto = pla.id
 *     LEFT OUTER JOIN mat_unidades un ON un.id = dd.unidade_medida
 *   WHERE est.date_time BETWEEN :inicio AND :termino
 *     AND dd.item_estoque = 1 AND est.tipo_movimentacao = 2
 *   GROUP BY 1, 2 WITH ROLLUP
 *
 * - `CONCAT`  -> string montada em PHP (colunas cruas no SELECT).
 * - `SUM()`   -> agregado por grupo; unico ponto de selectRaw (sem equivalente
 *               fluente para agregacao em GROUP BY).
 * - `WITH ROLLUP` -> reconstruido em PHP: detalhe, subtotal por conta contabil
 *               (material = null) e total geral (contabil = null).
 */
final class GastoAnualItensEstoqueService implements RelatorioService
{
    /**
     * @return Collection<int, array{contabil: ?string, material: ?string, qtde: float, valor: float}>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        // BUG-LEGADO: o legado faz BETWEEN com os valores crus do request
        // ("Y-m-d 00:00:00"), sem estender o termino ate o fim do dia. Mantido.
        $inicio = $filtros->inicioObrigatorio()->format('Y-m-d 00:00:00');
        $termino = $filtros->terminoObrigatorio()->format('Y-m-d 00:00:00');

        $linhas = MovimentacaoEstoque::query()
            ->from('egap_alm_estoque as est')
            ->join('egap_mat_descricaodetalhada as dd', 'est.material', '=', 'dd.id')
            ->join('egap_mat_descricaoresumida as dr', 'dd.descricao_resumida', '=', 'dr.id')
            ->join('egap_mat_produtos as pla', 'dr.id_produto', '=', 'pla.id')
            ->leftJoin('egap_mat_unidades as un', 'un.id', '=', 'dd.unidade_medida')
            ->whereBetween('est.date_time', [$inicio, $termino])
            ->where('dd.item_estoque', '1')
            ->where('est.tipo_movimentacao', 2)
            ->groupBy('pla.CodigodaClasse', 'pla.DescricaodaClasse', 'dd.descricao_detalhada', 'un.Sigla')
            ->orderBy('pla.CodigodaClasse')
            ->orderBy('dd.descricao_detalhada')
            ->select([
                'pla.CodigodaClasse as classe_codigo',
                'pla.DescricaodaClasse as classe_descricao',
                'dd.descricao_detalhada',
                'un.Sigla as unidade_sigla',
            ])
            ->selectRaw('SUM(est.quantidade) as qtde')
            ->selectRaw('SUM(est.valor_total) as valor')
            ->get()
            ->map(fn ($r): array => [
                'contabil' => trim(($r->classe_codigo ?? '').' - '.($r->classe_descricao ?? '')),
                'material' => $r->descricao_detalhada.' ('.$r->unidade_sigla.')',
                'qtde' => (float) $r->qtde,
                'valor' => (float) $r->valor,
            ]);

        return $this->comRollup($linhas);
    }

    /**
     * Reconstroi o comportamento de `GROUP BY 1, 2 WITH ROLLUP`.
     *
     * @param  Collection<int, array{contabil: ?string, material: ?string, qtde: float, valor: float}>  $linhas
     * @return Collection<int, array{contabil: ?string, material: ?string, qtde: float, valor: float}>
     */
    private function comRollup(Collection $linhas): Collection
    {
        $saida = collect();
        $totalGeralQtde = 0.0;
        $totalGeralValor = 0.0;

        foreach ($linhas->groupBy('contabil') as $contabil => $doGrupo) {
            $subQtde = 0.0;
            $subValor = 0.0;

            foreach ($doGrupo as $linha) {
                $saida->push($linha);
                $subQtde += $linha['qtde'];
                $subValor += $linha['valor'];
            }

            // Subtotal da conta contabil: material = null (ROLLUP nivel 1).
            $saida->push([
                'contabil' => (string) $contabil,
                'material' => null,
                'qtde' => $subQtde,
                'valor' => $subValor,
            ]);

            $totalGeralQtde += $subQtde;
            $totalGeralValor += $subValor;
        }

        if ($saida->isNotEmpty()) {
            // Total geral: contabil = null e material = null (ROLLUP nivel 0).
            $saida->push([
                'contabil' => null,
                'material' => null,
                'qtde' => $totalGeralQtde,
                'valor' => $totalGeralValor,
            ]);
        }

        return $saida->values();
    }
}
