<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Depreciacao;
use App\Models\egap\Patrimonio\BensMoveis\ItemBaixa;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Relatorio "Depreciacao Mensal" (Bens Moveis).
 *
 * Legado: relatorios-prints-queries/bens-moveis/depreciacao-mensal.php
 *
 * O legado monta uma base `pat` (conta x produto) e 7 LEFT JOINs. Porem SO
 * quatro alimentam a projecao:
 *   - tab1 SaldoAnterior       (SUM valor historico/reavaliado, regra de saldo anterior)
 *   - tab2 Entradas            (SUM valor historico/reavaliado, incorporadas no periodo)
 *   - depreciacao              (SUMs de mat_depreciacao no periodo, com regra de situacao)
 *   - depreciacao_saida        (dep. acumulada do mes anterior a baixa, por conta x produto)
 * BUG-LEGADO: `tab3` (Saidas) e `concil` (ValordaReavaliacao) sao calculados e
 * nunca usados no SELECT -> omitidos aqui (LEFT JOIN sem WHERE nao muda o
 * resultado).
 *
 * Projecao:
 *   Valor                = tab1 + tab2
 *   ValorResidual        = depreciacao.valor_residual
 *   DepreciacaoMensal    = depreciacao.depreciacao_mensal
 *   DepreciacaoAcumulada = depreciacao.depreciacao_acumulada
 *   ValorLiquidoContabil = Valor - DepreciacaoAcumulada
 *   DepreciacaoAcumuladaSaida = depreciacao_saida
 */
final class DepreciacaoMensalService implements RelatorioService
{
    private const ATIVOS = [1, 7];

    private const BAIXADOS = [2, 3, 4, 5, 6];

    private const ZERO_DATE = '0000-00-00 00:00:00';

    /**
     * @return Collection<int, array{
     *     conta_contabil: ?string, produto: ?string, descricao: ?string,
     *     valor: float, valor_residual: float, depreciacao_mensal: float,
     *     depreciacao_acumulada: float, valor_liquido_contabil: float,
     *     depreciacao_acumulada_saida: ?float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $termino = $filtros->terminoObrigatorio();
        $inicioMes = $termino->startOfMonth()->format('Y-m-d');   // legado: "$ano-$mes-01"
        $terminoData = $termino->format('Y-m-d');                  // legado: str_replace('00:00:00','')

        $inicioIni = $inicioMes.' 00:00:01';
        $terminoFim = $terminoData.' 23:59:59';
        $terminoIni = $terminoData.' 00:00:01';

        $base = $this->tuplasContaProduto($filtros);
        if ($base->isEmpty()) {
            return collect();
        }

        $tab1 = $this->saldoAnterior($filtros, $inicioIni, $terminoFim);
        $tab2 = $this->entradas($filtros, $inicioIni, $terminoFim);
        $depreciacao = $this->depreciacao($filtros, $inicioIni, $terminoIni, $terminoFim, $inicioMes, $terminoData);
        $depreciacaoSaida = $this->depreciacaoSaida($filtros, $inicioIni, $terminoFim);

        return $base->map(function ($t) use ($tab1, $tab2, $depreciacao, $depreciacaoSaida): array {
            $chave = $t->id_conta.'|'.$t->id_produto;

            $valor = (float) ($tab1[$chave]->valor ?? 0) + (float) ($tab2[$chave]->valor ?? 0);
            $dep = $depreciacao[$chave] ?? null;
            $depAcumulada = (float) ($dep->depreciacao_acumulada ?? 0);

            return [
                'conta_contabil' => $t->conta_contabil,
                'produto' => $t->produto,
                'descricao' => $t->descricao,
                'valor' => $valor,
                'valor_residual' => (float) ($dep->valor_residual ?? 0),
                'depreciacao_mensal' => (float) ($dep->depreciacao_mensal ?? 0),
                'depreciacao_acumulada' => $depAcumulada,
                'valor_liquido_contabil' => $valor - $depAcumulada,
                'depreciacao_acumulada_saida' => isset($depreciacaoSaida[$chave])
                    ? (float) $depreciacaoSaida[$chave]
                    : null,
            ];
        })->values();
    }

    /**
     * Aplica os filtros conta / situacao_contabil (o `$filtro` concatenado do legado).
     *
     * O legado tambem tinha `unidade_gestora`, mas o campo era sempre oculto no JS
     * e nunca chegava a este relatorio — omitido.
     */
    private function aplicarFiltro(BuilderContract $query, FiltrosRelatorio $filtros, string $prefixo = ''): BuilderContract
    {
        return $query
            ->when($filtros->conta, fn ($q) => $q->where($prefixo.'ContaContabil', $filtros->conta))
            ->when($filtros->situacaoContabil, fn ($q) => $q->where($prefixo.'situacao_contabil', $filtros->situacaoContabil));
    }

    /**
     * @return Collection<int, object>
     */
    private function tuplasContaProduto(FiltrosRelatorio $filtros): Collection
    {
        $query = BemMovel::query()
            ->from('egap_mat_patrimonio as p')
            ->join('egap_mat_planocontas as pc', 'p.ContaContabil', '=', 'pc.id')
            ->join('egap_mat_produtos as pr', 'p.Produto', '=', 'pr.id')
            ->groupBy('pc.id', 'pr.id', 'pc.codigo', 'pr.CodigodaClasse', 'pr.DescricaodaClasse')
            ->orderBy('pc.codigo')
            ->orderBy('pr.CodigodaClasse')
            ->select([
                'pc.id as id_conta',
                'pr.id as id_produto',
                'pc.codigo as conta_contabil',
                'pr.CodigodaClasse as produto',
                'pr.DescricaodaClasse as descricao',
            ]);

        $this->aplicarFiltro($query, $filtros, 'p.');

        return $query->get();
    }

    /**
     * tab1: regra de saldo anterior. SUM(IF(ValordaReavaliacao>0, ValordaReavaliacao, ValorAquisicao)).
     *
     * @return Collection<string, object>  keyBy "id_conta|id_produto"
     */
    private function saldoAnterior(FiltrosRelatorio $filtros, string $inicioIni, string $terminoFim): Collection
    {
        $query = BemMovel::query()
            ->groupBy('ContaContabil', 'Produto')
            ->select(['ContaContabil as id_conta', 'Produto as id_produto'])
            // SUM condicional (valor historico x reavaliado): agregado sem equivalente fluente.
            ->selectRaw('SUM(CASE WHEN ValordaReavaliacao > 0 THEN ValordaReavaliacao ELSE ValorAquisicao END) as valor')
            ->where(function ($q) use ($inicioIni, $terminoFim): void {
                $q->where(function ($q) use ($inicioIni, $terminoFim): void {
                    $q->where('DatadeIncorporacao', '<', $inicioIni)
                        ->whereIn('SituacaoBem', self::ATIVOS)
                        ->where(fn ($q) => $q->where('DatadaReavaliacao', '<', $terminoFim)->orWhere('DatadaReavaliacao', self::ZERO_DATE));
                })->orWhere(function ($q) use ($inicioIni, $terminoFim): void {
                    // Legado: DataBaixa >= '$termino 00:00:01' — usamos $inicioIni? nao: e o termino.
                    $q->where('DataBaixa', '>=', substr($terminoFim, 0, 10).' 00:00:01')
                        ->whereIn('SituacaoBem', self::BAIXADOS)
                        ->where(fn ($q) => $q->where('DatadaReavaliacao', '<', $terminoFim)->orWhere('DatadaReavaliacao', self::ZERO_DATE));
                });
            });

        $this->aplicarFiltro($query, $filtros);

        return $query->get()->keyBy(fn ($r) => $r->id_conta.'|'.$r->id_produto);
    }

    /**
     * tab2: entradas do periodo.
     *
     * @return Collection<string, object>
     */
    private function entradas(FiltrosRelatorio $filtros, string $inicioIni, string $terminoFim): Collection
    {
        $query = BemMovel::query()
            ->groupBy('ContaContabil', 'Produto')
            ->select(['ContaContabil as id_conta', 'Produto as id_produto'])
            ->selectRaw('SUM(CASE WHEN ValordaReavaliacao > 0 THEN ValordaReavaliacao ELSE ValorAquisicao END) as valor')
            ->whereBetween('DatadeIncorporacao', [$inicioIni, $terminoFim])
            ->whereNotIn('SituacaoBem', [8, 9]);

        $this->aplicarFiltro($query, $filtros);

        return $query->get()->keyBy(fn ($r) => $r->id_conta.'|'.$r->id_produto);
    }

    /**
     * depreciacao: SUMs de mat_depreciacao no periodo, com a regra de situacao do legado.
     *
     * @return Collection<string, object>
     */
    private function depreciacao(
        FiltrosRelatorio $filtros,
        string $inicioIni,
        string $terminoIni,
        string $terminoFim,
        string $inicioMes,
        string $terminoData,
    ): Collection {
        $query = BemMovel::query()
            ->from('egap_mat_patrimonio as p')
            ->join('egap_mat_depreciacao as d', 'p.id', '=', 'd.patrimonio')
            ->where('d.item', '>', 1)
            ->whereBetween('d.data_calculo', [$inicioMes, $terminoData])
            ->where(function ($q) use ($inicioIni, $terminoFim): void {
                $q->where(function ($q) use ($inicioIni): void {
                    $q->where('p.DatadeIncorporacao', '<', $inicioIni)
                        ->whereNotIn('p.SituacaoBem', [2, 3, 4, 5, 6, 8, 9])
                        ->where(fn ($q) => $q->where('p.DatadaReavaliacao', '<', $inicioIni)->orWhere('p.DatadaReavaliacao', self::ZERO_DATE));
                })->orWhere(function ($q) use ($inicioIni, $terminoFim): void {
                    $q->where('p.DataBaixa', '>', $terminoFim)
                        ->whereIn('p.SituacaoBem', self::BAIXADOS)
                        ->where(fn ($q) => $q->where('p.DatadaReavaliacao', '<', $inicioIni)->orWhere('p.DatadaReavaliacao', self::ZERO_DATE));
                });
            })
            ->groupBy('p.ContaContabil', 'p.Produto')
            ->select(['p.ContaContabil as id_conta', 'p.Produto as id_produto'])
            ->selectRaw('SUM(d.valor_residual) as valor_residual')
            ->selectRaw('SUM(d.depreciacao_mensal) as depreciacao_mensal')
            ->selectRaw('SUM(d.depreciacao_acumulada) as depreciacao_acumulada')
            ->selectRaw('SUM(d.valor_liquido_contabil) as valor_liquido_contabil');

        $this->aplicarFiltro($query, $filtros, 'p.');

        return $query->get()->keyBy(fn ($r) => $r->id_conta.'|'.$r->id_produto);
    }

    /**
     * depreciacao_saida: SUM da depreciacao acumulada do mes anterior a DataBaixa,
     * por (conta contabil, produto), para bens baixados no periodo.
     *
     * @return Collection<string, float>  keyBy "id_conta|id_produto"
     */
    private function depreciacaoSaida(FiltrosRelatorio $filtros, string $inicioIni, string $terminoFim): Collection
    {
        // Subquery (nao lista): mat_itembaixa tem ~70k linhas -> estourava o
        // limite de placeholders do MySQL num whereIn com a lista materializada.
        $bensQuery = BemMovel::query()
            ->whereIn('id', ItemBaixa::query()->where('id_baixa', '>', 0)->select('id_bem'))
            ->whereBetween('DataBaixa', [$inicioIni, $terminoFim])
            ->whereIn('SituacaoBem', self::BAIXADOS);

        $this->aplicarFiltro($bensQuery, $filtros);

        $bens = $bensQuery->get(['id', 'ContaContabil', 'Produto', 'DataBaixa']);
        if ($bens->isEmpty()) {
            return collect();
        }

        // mat_depreciacao tem milhoes de linhas: agrupa os bens pelo mes-alvo
        // (mes anterior a DataBaixa) e consulta um mes por vez.
        $mesAlvoPorBem = $bens
            ->filter(fn (BemMovel $b) => $b->DataBaixa)
            ->mapWithKeys(fn (BemMovel $b) => [$b->id => Carbon::parse($b->DataBaixa)->subMonthNoOverflow()]);

        $depAcumuladaPorBem = collect();

        // preserveKeys: as chaves sao os ids dos bens, usados no whereIn abaixo.
        foreach ($mesAlvoPorBem->groupBy(fn (Carbon $d) => $d->format('Y-m'), preserveKeys: true) as $doMes) {
            $ref = $doMes->first();

            $depAcumuladaPorBem = $depAcumuladaPorBem->union(
                Depreciacao::query()
                    ->toBase()
                    // Sem o hint o MySQL varre o mes inteiro pelo indice de data.
                    ->forceIndex('idx_mat_depreciacao_patrimonio')
                    ->whereIn('patrimonio', $doMes->keys())
                    // BETWEEN (sargable) em vez de whereYear/whereMonth (funcao na coluna
                    // impede o uso de indice numa tabela de milhoes de linhas).
                    ->whereBetween('data_calculo', [
                        $ref->copy()->startOfMonth()->format('Y-m-d 00:00:00'),
                        $ref->copy()->endOfMonth()->format('Y-m-d 23:59:59'),
                    ])
                    ->get(['patrimonio', 'depreciacao_acumulada'])
                    ->keyBy('patrimonio')
                    ->map(fn (object $dep) => (float) $dep->depreciacao_acumulada)
            );
        }

        return $bens
            ->groupBy(fn (BemMovel $b) => $b->ContaContabil.'|'.$b->Produto)
            ->map(fn (Collection $doGrupo) => $doGrupo->sum(fn (BemMovel $b) => $depAcumuladaPorBem[$b->id] ?? 0.0));
    }
}
