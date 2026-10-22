<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensImoveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensImoveis\Depreciacao;
use App\Models\egap\Patrimonio\BensImoveis\Obra;
use App\Models\egap\Patrimonio\BensImoveis\Reavaliacao;
use App\Services\Egap\CentroCustoSetor;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Collection;

/**
 * Relatorio "Depreciacao Mensal Imoveis por Centro de Custo"
 * (agrupado por data da disponibilizacao).
 *
 * Legado: relatorios-prints-queries/bens-imoveis/depreciacao-mensal-imoveis-agrupado-data-da-disponibilizacao.php
 *
 * Mesma logica do "Depreciacao Mensal Imoveis" (rea = termino-1, dep = termino-2,
 * obras <= termino), porem:
 *   - itera por centro de custo (imo_imovel.Id_setores IN setores do CC);
 *   - junta mat_produtos (via id_elementodespesa) para Produto e item_patrimonial;
 *   - TRUNCATE(...,2) nos valores.
 *
 * `gerar()` = detalhe (linhas imovel, com o CC).
 * `resumos()` = os 3 blocos de totalizacao (por CC, por conta, por item patrimonial).
 */
final class DepreciacaoMensalImoveisCcService implements RelatorioService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $termino = $filtros->terminoObrigatorio();
        $mesTermino = $termino->startOfMonth();
        $mesRea = $mesTermino->copy()->subMonthNoOverflow();
        $mesDep = $mesTermino->copy()->subMonthsNoOverflow(2);

        // Uma consulta para todos os centros de custo (o legado repete por centro).
        $setorParaCentro = CentroCustoSetor::mapaSetores($filtros->centroCusto);
        if ($setorParaCentro === []) {
            return collect();
        }

        $linhas = Depreciacao::query()
            ->where('item', '>', 1)
            ->whereYear('data_calculo', $mesTermino->year)
            ->whereMonth('data_calculo', $mesTermino->month)
            ->whereHas('imovelRelacaoref', fn ($q) => $q->where('Id_situacao', 1)->whereIn('Id_setores', array_keys($setorParaCentro)))
            ->with(['imovelRelacaoref' => fn ($q) => $q
                ->with('planoContasRelacaoRef:id,codigo,titulo', 'elementoDespesaRelacaoRef:id,CodigodaClasse,DescricaodaClasse,item_patrimonial')])
            ->get();

        if ($linhas->isEmpty()) {
            return collect();
        }

        $imovelIds = $linhas->pluck('Id_imovel')->unique()->values()->all();

        $reaPorImovel = Reavaliacao::query()
            ->whereIn('Id_imovel', $imovelIds)
            ->whereYear('data_reavaliacao', $mesRea->year)
            ->whereMonth('data_reavaliacao', $mesRea->month)
            ->get(['Id_imovel', 'valor_reavaliacao'])
            ->keyBy('Id_imovel')
            ->map(fn ($r) => (float) ($r->valor_reavaliacao ?? 0));

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
            ->whereDate('data', '<=', $termino->format('Y-m-d'))
            ->groupBy('id_imovel')
            ->select('id_imovel')
            ->selectRaw('SUM(valor) as valor_obra')
            ->pluck('valor_obra', 'id_imovel');

        $porCentro = $linhas->groupBy(fn (Depreciacao $d) => $setorParaCentro[(int) $d->imovelRelacaoref->Id_setores]);

        return CentroCustoSetor::listar($filtros->centroCusto)
            ->flatMap(fn (object $centro) => ($porCentro[$centro->codigo] ?? collect())
                ->map(fn (Depreciacao $d): array => $this->linha($d, $centro, $reaPorImovel, $depAnteriorPorImovel, $obraPorImovel))
                ->sort(fn ($a, $b) => [(string) $a['conta_contabil'], (string) $a['descricao_imovel']]
                    <=> [(string) $b['conta_contabil'], (string) $b['descricao_imovel']])
                ->values())
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $detalhe
     * @return array{por_centro_custo: list<array<string, mixed>>, por_conta: list<array<string, mixed>>, por_item_patrimonial: list<array<string, mixed>>}
     */
    public function resumos(Collection $detalhe): array
    {
        $medidas = ['valor', 'valor_residual', 'depreciacao_mensal', 'depreciacao_acumulada', 'valor_liquido_contabil', 'depreciacao_acumulada_reavaliacao'];
        $somar = fn (Collection $linhas) => collect($medidas)->mapWithKeys(fn ($m) => [$m => (float) $linhas->sum($m)])->all();

        return [
            'por_centro_custo' => $detalhe->groupBy('centro_custo_codigo')
                ->map(fn (Collection $l) => ['codigo' => $l->first()['centro_custo_codigo'], 'centro_custo' => $l->first()['centro_custo_descricao'], ...$somar($l)])
                ->values()->all(),
            'por_conta' => $detalhe->groupBy('conta_contabil')
                ->map(fn (Collection $l) => ['conta_contabil' => $l->first()['conta_contabil'], 'produto' => $l->first()['produto'], 'descricao' => $l->first()['descricao_conta'], ...$somar($l)])
                ->values()->all(),
            'por_item_patrimonial' => $detalhe->groupBy('item_patrimonial')
                ->map(fn (Collection $l) => ['item_patrimonial' => $l->first()['item_patrimonial'], ...$somar($l)])
                ->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, float>  $reaPorImovel
     * @param  Collection<int, Depreciacao>  $depAnteriorPorImovel
     * @param  Collection<int, mixed>  $obraPorImovel
     * @return array<string, mixed>
     */
    private function linha(
        Depreciacao $d,
        object $centro,
        Collection $reaPorImovel,
        Collection $depAnteriorPorImovel,
        Collection $obraPorImovel,
    ): array {
        $imovel = $d->imovelRelacaoref;
        $produto = $imovel?->elementoDespesaRelacaoRef;
        $obra = (float) ($obraPorImovel[$d->Id_imovel] ?? 0);
        $reavaliacao = (float) ($reaPorImovel[$d->Id_imovel] ?? 0);
        $depAnterior = (float) ($depAnteriorPorImovel[$d->Id_imovel]->depreciacao_acumulada ?? 0);

        return [
            'centro_custo_codigo' => (string) $centro->codigo,
            'centro_custo_descricao' => $centro->descricao,
            'conta_contabil' => $imovel?->planoContasRelacaoRef?->codigo,
            'descricao_conta' => $imovel?->planoContasRelacaoRef?->titulo,
            'produto' => $produto?->CodigodaClasse,
            'item_patrimonial' => $produto?->item_patrimonial,
            'num_imovel' => $imovel?->num_registro,
            'descricao_imovel' => $imovel?->descricao,
            'inscricao_generica' => $imovel?->inscricao_generica,
            'valor' => $this->truncate((float) $d->valor + $obra, 2),
            'depreciacao_mensal' => $this->truncate((float) $d->depreciacao_mensal, 2),
            'depreciacao_acumulada' => $this->truncate((float) $d->depreciacao_acumulada, 2),
            'valor_residual' => $this->truncate((float) $d->valor_residual, 2),
            'valor_liquido_contabil' => $this->truncate((float) $d->valor_liquido_contabil + $obra, 2),
            'depreciacao_acumulada_reavaliacao' => $reavaliacao > 0 ? $this->truncate($depAnterior, 2) : 0.0,
        ];
    }

    /**
     * TRUNCATE(valor, casas) do MySQL. Em float, (int) (77.6 * 100) da 7759
     * (77.6 * 100 = 7759.99...), entao trunca sobre a representacao decimal.
     */
    private function truncate(float $valor, int $casas): float
    {
        return BigDecimal::of((string) $valor)->toScale($casas, RoundingMode::Down)->toFloat();
    }
}
