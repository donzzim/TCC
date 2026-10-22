<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\TCE;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Support\ConsultaPatrimonioContaProduto;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use Illuminate\Support\Collection;

/**
 * TCE - Tabela 10: Resumo do Inventario dos Bens Moveis.
 *
 * Legado: relatorios-prints-queries/tce/tabela-10.php
 *
 * Base `pat` (conta contabil x produto de mat_patrimonio) + LEFT:
 *   - tab1           : SUM(valor historico/reavaliado) — regra de saldo anterior (3 OR-branches)
 *   - anterior_baixa : idem, para baixas futuras incorporadas no periodo (subtraido de tab1)
 *   - tab2           : entradas do periodo
 *   - tab3           : saidas do periodo
 *   - concil         : SUM(ValordaReavaliacao) das reavaliacoes do periodo
 *   - depreciacao    : SUM(depreciacao_acumulada) de mat_depreciacao no mes do termino
 *
 * Projecao:
 *   SaldoAnterior = tab1 - anterior_baixa
 *   Entradas      = tab2 + concil
 *   Saidas        = tab3
 *   SaldoBruto    = SaldoAnterior + Entradas - Saidas
 *   SaldoAtual    = SaldoBruto - DepreciacaoAcumulada
 */
final class Tabela10Service implements RelatorioService
{
    use ConsultaPatrimonioContaProduto;

    private const CORTE = '2015-01-01 00:00:01';

    private const ATIVOS_NAO_BAIXADOS = [2, 3, 4, 5, 6, 8, 9];

    private const BAIXADOS = [2, 3, 4, 5, 6];

    /**
     * @return Collection<int, array{
     *     conta_contabil: ?string, produto: ?string, descricao: ?string,
     *     saldo_anterior: float, entradas: float, saidas: float,
     *     saldo_bruto: float, depreciacao_acumulada: float, saldo_atual: float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $termino = $filtros->terminoObrigatorio();
        $inicio = $filtros->inicioHora();   // Y-m-d 00:00:01
        $fim = $filtros->fimHora();          // Y-m-d 23:59:59
        $inicioDep = $termino->startOfMonth()->format('Y-m-d').' 00:00:01';
        $expr = $this->somaValorHistorico(self::CORTE);

        $base = $this->tuplasPatrimonioContaProduto($filtros);
        if ($base->isEmpty()) {
            return collect();
        }

        $tab1 = $this->somaPatrimonioPorContaProduto($filtros, $expr, function ($q) use ($inicio, $fim): void {
            $q->where(function ($q) use ($inicio, $fim): void {
                $q->where(function ($q) use ($inicio): void {
                    $q->where('DatadeIncorporacao', '<', $inicio)
                        ->whereNotIn('SituacaoBem', self::ATIVOS_NAO_BAIXADOS);
                    $this->reavaliacaoAntesDe($q, $inicio);
                })->orWhere(function ($q) use ($inicio, $fim): void {
                    $q->where('DataBaixa', '>=', $fim)->whereIn('SituacaoBem', self::BAIXADOS);
                    $this->reavaliacaoAntesDe($q, $inicio);
                })->orWhere(function ($q) use ($inicio, $fim): void {
                    $q->whereBetween('DataBaixa', [$inicio, $fim])->whereIn('SituacaoBem', self::BAIXADOS);
                    $this->reavaliacaoAntesDe($q, $inicio);
                });
            });
        });

        $anteriorBaixa = $this->somaPatrimonioPorContaProduto($filtros, $expr, function ($q) use ($inicio, $fim): void {
            $q->where('DataBaixa', '>=', $fim)->whereBetween('DatadeIncorporacao', [$inicio, $fim]);
        });

        $tab2 = $this->somaPatrimonioPorContaProduto($filtros, $expr, function ($q) use ($inicio, $fim): void {
            $q->whereBetween('DatadeIncorporacao', [$inicio, $fim])->whereNotIn('SituacaoBem', [8, 9]);
        });

        $tab3 = $this->somaPatrimonioPorContaProduto($filtros, $expr, function ($q) use ($inicio, $fim): void {
            $q->whereBetween('DataBaixa', [$inicio, $fim])->whereIn('SituacaoBem', self::BAIXADOS);
        });

        $concil = $this->somaPatrimonioPorContaProduto($filtros, 'SUM(ValordaReavaliacao)', function ($q) use ($inicio, $fim): void {
            $q->whereBetween('DatadaReavaliacao', [$inicio, $fim])->whereNotIn('SituacaoBem', [8, 9]);
        });

        $depreciacao = $this->depreciacao($filtros, $termino->year, $termino->month, $inicioDep, $fim);

        return $base->map(function ($t) use ($tab1, $anteriorBaixa, $tab2, $tab3, $concil, $depreciacao): array {
            $chave = $t->id_conta.'|'.$t->id_produto;

            $saldoAnterior = (float) ($tab1[$chave]->valor ?? 0) - (float) ($anteriorBaixa[$chave]->valor ?? 0);
            $entradas = (float) ($tab2[$chave]->valor ?? 0) + (float) ($concil[$chave]->valor ?? 0);
            $saidas = (float) ($tab3[$chave]->valor ?? 0);
            $saldoBruto = $saldoAnterior + $entradas - $saidas;
            $depAcumulada = (float) ($depreciacao[$chave]->valor ?? 0);

            return [
                'conta_contabil' => $t->conta_contabil,
                'produto' => $t->produto,
                'descricao' => $t->descricao,
                'saldo_anterior' => $saldoAnterior,
                'entradas' => $entradas,
                'saidas' => $saidas,
                'saldo_bruto' => $saldoBruto,
                'depreciacao_acumulada' => $depAcumulada,
                'saldo_atual' => $saldoBruto - $depAcumulada,
            ];
        })->values();
    }

    /**
     * depreciacao: SUM(d.depreciacao_acumulada) de mat_depreciacao no mes do
     * termino, com a regra de situacao do legado (usa inicio_dep).
     *
     * @return Collection<string, object>
     */
    private function depreciacao(FiltrosRelatorio $filtros, int $anoTermino, int $mesTermino, string $inicioDep, string $fim): Collection
    {
        $mesRef = \Illuminate\Support\Carbon::create($anoTermino, $mesTermino, 1);

        $query = BemMovel::query()
            ->from('egap_mat_patrimonio as p')
            ->join('egap_mat_depreciacao as d', 'p.id', '=', 'd.patrimonio')
            ->where('d.item', '>', 1)
            // BETWEEN sargable: mat_depreciacao tem milhoes de linhas; whereYear/
            // whereMonth (funcao na coluna) forcaria full scan.
            ->whereBetween('d.data_calculo', [
                $mesRef->copy()->startOfMonth()->format('Y-m-d 00:00:00'),
                $mesRef->copy()->endOfMonth()->format('Y-m-d 23:59:59'),
            ])
            ->where(function ($q) use ($inicioDep, $fim): void {
                $q->where(function ($q) use ($inicioDep): void {
                    $q->where('p.DatadeIncorporacao', '<', $inicioDep)
                        ->whereNotIn('p.SituacaoBem', self::ATIVOS_NAO_BAIXADOS);
                    $this->reavaliacaoAntesDe($q, $inicioDep, 'p.');
                })->orWhere(function ($q) use ($inicioDep, $fim): void {
                    $q->where('p.DataBaixa', '>', $fim)->whereIn('p.SituacaoBem', self::BAIXADOS);
                    $this->reavaliacaoAntesDe($q, $inicioDep, 'p.');
                });
            })
            ->groupBy('p.ContaContabil', 'p.Produto')
            ->select(['p.ContaContabil as id_conta', 'p.Produto as id_produto'])
            ->selectRaw('SUM(d.depreciacao_acumulada) as valor');

        $this->aplicarFiltroPatrimonio($query, $filtros, 'p.');

        return $query->get()->keyBy(fn ($r) => $r->id_conta.'|'.$r->id_produto);
    }
}
