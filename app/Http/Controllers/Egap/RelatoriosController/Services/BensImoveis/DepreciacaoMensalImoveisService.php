<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensImoveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensImoveis\Depreciacao;
use App\Models\egap\Patrimonio\BensImoveis\Obra;
use App\Models\egap\Patrimonio\BensImoveis\Reavaliacao;
use Illuminate\Support\Collection;

/**
 * Relatorio "Depreciacao Mensal Imoveis".
 *
 * Legado: relatorios-prints-queries/bens-imoveis/depreciacao-mensal-imoveis.php
 *
 * Uma linha por imo_depreciacao (item > 1) com data_calculo no mes do termino,
 * cujo imovel esta ativo (Id_situacao = 1). Auxiliares:
 *   - rea  = imo_reavaliacao no mes (termino - 1)
 *   - dep  = imo_depreciacao no mes (termino - 2), por imovel
 *   - obra = SUM(imo_obras.valor) com data <= termino, por imovel
 *
 * Projecao:
 *   Valor                = imo_depreciacao.valor + IFNULL(obra,0)
 *   ValorLiquidoContabil = imo_depreciacao.valor_liquido_contabil + IFNULL(obra,0)
 *   DepreciacaoAcumuladaReavaliacao = IF(rea.valor_reavaliacao > 0, dep.depreciacao_acumulada, 0)
 */
final class DepreciacaoMensalImoveisService implements RelatorioService
{
    /**
     * @return Collection<int, array{
     *     conta_contabil: ?string, descricao_conta: ?string, num_imovel: ?string,
     *     descricao_imovel: ?string, inscricao_generica: ?string,
     *     valor: float, depreciacao_mensal: float, depreciacao_acumulada: float,
     *     valor_residual: float, valor_liquido_contabil: float,
     *     depreciacao_acumulada_reavaliacao: float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $termino = $filtros->terminoObrigatorio();
        $mesTermino = $termino->startOfMonth();
        $terminoData = $termino->format('Y-m-d');

        $linhas = Depreciacao::query()
            ->where('item', '>', 1)
            ->whereYear('data_calculo', $mesTermino->year)
            ->whereMonth('data_calculo', $mesTermino->month)
            ->whereHas('imovelRelacaoref', fn ($q) => $q->where('Id_situacao', 1))
            ->with(['imovelRelacaoref' => fn ($q) => $q->with('planoContasRelacaoRef:id,codigo,titulo')])
            ->get();

        if ($linhas->isEmpty()) {
            return collect();
        }

        $imovelIds = $linhas->pluck('Id_imovel')->unique()->values()->all();

        $mesRea = $mesTermino->copy()->subMonthNoOverflow();
        $reaPorImovel = Reavaliacao::query()
            ->whereIn('Id_imovel', $imovelIds)
            ->whereYear('data_reavaliacao', $mesRea->year)
            ->whereMonth('data_reavaliacao', $mesRea->month)
            ->get(['Id_imovel', 'valor_reavaliacao'])
            ->keyBy('Id_imovel')
            ->map(fn ($r) => (float) ($r->valor_reavaliacao ?? 0));

        $mesDep = $mesTermino->copy()->subMonthsNoOverflow(2);
        $depAnteriorPorImovel = Depreciacao::query()
            ->whereIn('Id_imovel', $imovelIds)
            ->whereYear('data_calculo', $mesDep->year)
            ->whereMonth('data_calculo', $mesDep->month)
            // Mes da reavaliacao tem 2 linhas: o fim do cronograma antigo e o item 1 (zerado)
            // do novo. O legado (GROUP BY sem agregado) fica com a primeira; keyBy ficaria
            // com a ultima e zeraria a coluna.
            ->orderBy('id')
            ->get(['Id_imovel', 'depreciacao_acumulada'])
            ->unique('Id_imovel')
            ->keyBy('Id_imovel');

        $obraPorImovel = Obra::query()
            ->whereIn('id_imovel', $imovelIds)
            ->whereDate('data', '<=', $terminoData)
            ->groupBy('id_imovel')
            ->select('id_imovel')
            ->selectRaw('SUM(valor) as valor_obra')
            ->pluck('valor_obra', 'id_imovel');

        return $linhas
            ->map(function (Depreciacao $d) use ($reaPorImovel, $depAnteriorPorImovel, $obraPorImovel): array {
                $imovel = $d->imovelRelacaoref;
                $obra = (float) ($obraPorImovel[$d->Id_imovel] ?? 0);
                $reavaliacao = (float) ($reaPorImovel[$d->Id_imovel] ?? 0);
                $depAnterior = (float) ($depAnteriorPorImovel[$d->Id_imovel]->depreciacao_acumulada ?? 0);

                return [
                    'conta_contabil' => $imovel?->planoContasRelacaoRef?->codigo,
                    'descricao_conta' => $imovel?->planoContasRelacaoRef?->titulo,
                    'num_imovel' => $imovel?->num_registro,
                    'descricao_imovel' => $imovel?->descricao,
                    'inscricao_generica' => $imovel?->inscricao_generica,
                    'valor' => (float) $d->valor + $obra,
                    'depreciacao_mensal' => (float) $d->depreciacao_mensal,
                    'depreciacao_acumulada' => (float) $d->depreciacao_acumulada,
                    'valor_residual' => (float) $d->valor_residual,
                    'valor_liquido_contabil' => (float) $d->valor_liquido_contabil + $obra,
                    'depreciacao_acumulada_reavaliacao' => $reavaliacao > 0 ? $depAnterior : 0.0,
                ];
            })
            // Legado: ORDER BY mat_planocontas.codigo, imo_imovel.descricao
            ->sort(fn ($a, $b) => [(string) $a['conta_contabil'], (string) $a['descricao_imovel']]
                <=> [(string) $b['conta_contabil'], (string) $b['descricao_imovel']])
            ->values();
    }
}
