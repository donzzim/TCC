<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensImoveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensImoveis\Depreciacao;
use App\Models\egap\Patrimonio\BensImoveis\Reavaliacao;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Relatorio "Ajustes apos Reavaliacao dos Imoveis".
 *
 * Legado: relatorios-prints-queries/bens-imoveis/ajustes-reavaliacao-imoveis.php
 *
 * Uma linha por reavaliacao (imo_reavaliacao) com data_reavaliacao no periodo,
 * cujo imovel esta ativo (Id_situacao = 1). Para cada uma:
 *   - valorbruto = valor_reavaliacao da 3a reavaliacao mais recente do imovel
 *                  (legado: subquery ... ORDER BY data_reavaliacao DESC LIMIT 2,1)
 *   - dep = imo_depreciacao do mes ANTERIOR ao inicio, com depreciacao_acumulada > 0
 *   - valor = IF(dep.valor > 0, dep.valor,
 *                IF(valorbruto > 0, valorbruto, imovel.valor_historico_1a_avaliacao))
 *   ORDER BY conta.codigo, imovel.descricao
 */
final class AjustesReavaliacaoImoveisService implements RelatorioService
{
    /**
     * @return Collection<int, array{
     *     conta_contabil: ?string, descricao_conta: ?string, num_imovel: ?string,
     *     descricao_imovel: ?string, inscricao_generica: ?string,
     *     valor: float, depreciacao_acumulada: float, valor_liquido_contabil: float,
     *     data_reavaliacao: ?string, valor_reavaliacao: float, ajuste_contabil: float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicio = $filtros->inicioData();
        $termino = $filtros->fimData();

        $reavaliacoes = Reavaliacao::query()
            ->whereBetween('data_reavaliacao', [$inicio.' 00:00:01', $termino.' 23:59:59'])
            ->whereHas('imovelRelacaoref', fn ($q) => $q->where('Id_situacao', 1))
            ->with(['imovelRelacaoref' => fn ($q) => $q->with('planoContasRelacaoRef:id,codigo,titulo')])
            ->get();

        if ($reavaliacoes->isEmpty()) {
            return collect();
        }

        $imovelIds = $reavaliacoes->pluck('Id_imovel')->unique()->values()->all();

        // 3a reavaliacao mais recente por imovel (LIMIT 2,1 do legado).
        $valorBrutoPorImovel = Reavaliacao::query()
            ->whereIn('Id_imovel', $imovelIds)
            ->orderBy('Id_imovel')
            ->orderByDesc('data_reavaliacao')
            ->get(['Id_imovel', 'valor_reavaliacao', 'data_reavaliacao'])
            ->groupBy('Id_imovel')
            ->map(fn (Collection $g) => $g->values()->get(2)?->valor_reavaliacao)
            ->map(fn ($v) => (float) ($v ?? 0));

        // imo_depreciacao do mes (inicio - 1 mes), depreciacao_acumulada > 0.
        $mesDep = Carbon::parse($inicio)->subMonthNoOverflow();
        $depPorImovel = Depreciacao::query()
            ->whereIn('Id_imovel', $imovelIds)
            ->where('depreciacao_acumulada', '>', 0)
            ->whereYear('data_calculo', $mesDep->year)
            ->whereMonth('data_calculo', $mesDep->month)
            ->get(['Id_imovel', 'valor', 'depreciacao_acumulada', 'valor_liquido_contabil'])
            ->keyBy('Id_imovel');

        return $reavaliacoes
            ->map(function (Reavaliacao $rea) use ($valorBrutoPorImovel, $depPorImovel): array {
                $imovel = $rea->imovelRelacaoref;
                $dep = $depPorImovel[$rea->Id_imovel] ?? null;
                $valorBruto = (float) ($valorBrutoPorImovel[$rea->Id_imovel] ?? 0);

                $valor = match (true) {
                    (float) ($dep->valor ?? 0) > 0 => (float) $dep->valor,
                    $valorBruto > 0 => $valorBruto,
                    default => (float) ($imovel?->valor_historico_1a_avaliacao ?? 0),
                };

                return [
                    'conta_contabil' => $imovel?->planoContasRelacaoRef?->codigo,
                    'descricao_conta' => $imovel?->planoContasRelacaoRef?->titulo,
                    'num_imovel' => $imovel?->num_registro,
                    'descricao_imovel' => $imovel?->descricao,
                    'inscricao_generica' => $imovel?->inscricao_generica,
                    'valor' => $valor,
                    'depreciacao_acumulada' => (float) ($dep->depreciacao_acumulada ?? 0),
                    'valor_liquido_contabil' => (float) ($dep->valor_liquido_contabil ?? 0),
                    'data_reavaliacao' => $rea->data_reavaliacao ? Carbon::parse($rea->data_reavaliacao)->format('d/m/Y') : null,
                    'valor_reavaliacao' => (float) $rea->valor_reavaliacao,
                    'ajuste_contabil' => (float) $rea->ajuste_contabil,
                ];
            })
            // Legado: ORDER BY mat_planocontas.codigo, imo_imovel.descricao
            ->sort(fn ($a, $b) => [(string) $a['conta_contabil'], (string) $a['descricao_imovel']]
                <=> [(string) $b['conta_contabil'], (string) $b['descricao_imovel']])
            ->values();
    }
}
