<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensMoveis\Baixa;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Depreciacao;
use App\Models\egap\Patrimonio\BensMoveis\ItemBaixa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Relatorio "Bens Baixados".
 *
 * Legado: relatorios-prints-queries/bens-moveis/bens-baixados.php  (3 queries)
 *
 * Query 1: processos de baixa (mat_baixa) no periodo.
 * Query 2 (no legado em loop por processo): itens baixados com
 *   ValorBruto (corte 2015), DepreciacaoAcumulada e ValorLiquidoContabil vindos
 *   da linha de mat_depreciacao do MES ANTERIOR a DataBaixa (subquery
 *   correlacionada -> resolvida em PHP).
 * Query 3: resumo "depreciacao das saidas" por (conta contabil, processo).
 *   -> resumoDepreciacaoSaidas()
 */
final class BensBaixadosService implements RelatorioService
{
    private const CORTE_HISTORICO = '2015-01-01 00:00:00';

    private const SITUACOES_BAIXA = [2, 3, 4, 5, 6];

    /**
     * @return Collection<int, array{
     *     id: int, numero_processo: ?string, requisitante: ?string, requisitante_cnpj: ?string,
     *     data_baixa: ?string, observacao: ?string, endereco: ?string,
     *     itens: list<array{
     *         conta_codigo: ?string, num_patrimonio: mixed, descricao: ?string,
     *         marca: ?string, modelo: ?string, valor_bruto: float,
     *         depreciacao_acumulada: float, valor_liquido_contabil: float, situacao: ?string
     *     }>
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicio = $filtros->inicioHora();
        $termino = $filtros->fimHora();

        $processos = Baixa::query()
            ->whereBetween('DataBaixa', [$inicio, $termino])
            ->orderBy('NumeroProcesso')
            ->get(['id', 'NumeroProcesso', 'Requisitante', 'RequisitanteCnpj', 'DataBaixa', 'Observacao', 'Endereco']);

        if ($processos->isEmpty()) {
            return collect();
        }

        $itensPorProcesso = $this->itensPorProcesso($processos->pluck('id')->all(), $inicio, $termino, $filtros);

        return $processos->map(fn (Baixa $p): array => [
            'id' => (int) $p->id,
            'numero_processo' => $p->NumeroProcesso,
            'requisitante' => $p->Requisitante,
            'requisitante_cnpj' => $p->RequisitanteCnpj,
            'data_baixa' => $p->DataBaixa ? Carbon::parse($p->DataBaixa)->format('d/m/Y') : null,
            'observacao' => $p->Observacao,
            'endereco' => $p->Endereco,
            'itens' => ($itensPorProcesso[$p->id] ?? collect())->values()->all(),
        ])->values();
    }

    /**
     * Query 3 do legado: SUM de ValorBruto / ValorLiquidoContabil /
     * DepreciacaoAcumulada por (conta contabil, numero do processo).
     *
     * @param  Collection<int, array<string, mixed>>  $processos  saida de gerar()
     * @return list<array{conta_codigo: ?string, numero_processo: ?string, valor_bruto: float, valor_liquido_contabil: float, depreciacao_acumulada: float}>
     */
    public function resumoDepreciacaoSaidas(Collection $processos): array
    {
        return $processos
            ->flatMap(fn (array $p) => collect($p['itens'])->map(fn (array $item) => $item + ['numero_processo' => $p['numero_processo']]))
            ->groupBy(fn (array $item) => $item['conta_codigo'].'|'.$item['numero_processo'])
            ->map(fn (Collection $linhas) => [
                'conta_codigo' => $linhas->first()['conta_codigo'],
                'numero_processo' => $linhas->first()['numero_processo'],
                'valor_bruto' => (float) $linhas->sum('valor_bruto'),
                'valor_liquido_contabil' => (float) $linhas->sum('valor_liquido_contabil'),
                'depreciacao_acumulada' => (float) $linhas->sum('depreciacao_acumulada'),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $baixaIds
     * @return Collection<int, Collection<int, array<string, mixed>>>  keyBy id_baixa
     */
    private function itensPorProcesso(array $baixaIds, string $inicio, string $termino, FiltrosRelatorio $filtros): Collection
    {
        $itensBaixa = ItemBaixa::query()
            ->whereIn('id_baixa', $baixaIds)
            ->where('id_baixa', '>', 0)
            ->get(['id_baixa', 'id_bem']);

        $bemIds = $itensBaixa->pluck('id_bem')->unique()->values()->all();

        if ($bemIds === []) {
            return collect();
        }

        $bens = BemMovel::query()
            ->whereIn('id', $bemIds)
            ->whereBetween('DataBaixa', [$inicio, $termino])
            ->whereIn('SituacaoBem', self::SITUACOES_BAIXA)
            ->when($filtros->conta, fn ($q) => $q->where('ContaContabil', $filtros->conta))
            // Filtro de unidade_gestora do legado nunca era enviado (campo oculto no JS). Omitido.
            ->when($filtros->situacaoContabil, fn ($q) => $q->where('situacao_contabil', $filtros->situacaoContabil))
            ->with([
                'contaContabilRef:id,codigo',
                'descricaoResumidaBemRef:id,Descricao',
                'marcaRef:id,descricao',
                'modeloRef:id,descricao',
                'situacaoBemRef:id,descricao',
            ])
            ->get([
                'id', 'ContaContabil', 'NumPatrimonio', 'DescricaoResumidadoBem', 'Marca', 'Modelo',
                'SituacaoBem', 'DataBaixa', 'DatadeIncorporacao', 'ValordaReavaliacao', 'ValorAquisicao',
            ])
            ->keyBy('id');

        $depreciacaoPorBem = $this->depreciacaoAnteriorABaixa($bens);

        return $itensBaixa
            ->groupBy('id_baixa')
            ->map(function (Collection $doProcesso) use ($bens, $depreciacaoPorBem): Collection {
                return $doProcesso
                    ->map(function (ItemBaixa $ib) use ($bens, $depreciacaoPorBem): ?array {
                        $bem = $bens[$ib->id_bem] ?? null;
                        if ($bem === null) {
                            return null;
                        }

                        $dep = $depreciacaoPorBem[$bem->id] ?? null;

                        return [
                            'conta_codigo' => $bem->contaContabilRef?->codigo,
                            'num_patrimonio' => $bem->NumPatrimonio,
                            'descricao' => $bem->descricaoResumidaBemRef?->Descricao,
                            'marca' => $bem->marcaRef?->descricao,
                            'modelo' => $bem->modeloRef?->descricao,
                            'valor_bruto' => $this->valorBruto($bem),
                            'depreciacao_acumulada' => (float) ($dep->depreciacao_acumulada ?? 0),
                            // Legado: IFNULL(subquery.valor_liquido_contabil, ValordaReavaliacao)
                            'valor_liquido_contabil' => (float) ($dep->valor_liquido_contabil ?? $bem->ValordaReavaliacao ?? 0),
                            'situacao' => $bem->situacaoBemRef?->descricao,
                        ];
                    })
                    ->filter()
                    ->sort(fn ($a, $b) => [(string) $a['conta_codigo'], (string) $a['num_patrimonio']]
                        <=> [(string) $b['conta_codigo'], (string) $b['num_patrimonio']])
                    ->values();
            });
    }

    /**
     * Linha de mat_depreciacao do mes anterior a DataBaixa, por bem.
     *
     * Legado: subquery correlacionada
     *   WHERE patrimonio = bem.id
     *     AND DATE_FORMAT(data_calculo,'%m%Y') = DATE_FORMAT(DataBaixa + INTERVAL -1 MONTH,'%m%Y')
     *   LIMIT 1
     *
     * mat_depreciacao tem milhoes de linhas — em vez de carregar todas as do bem,
     * agrupamos os bens pelo mes-alvo e consultamos um mes por vez (poucos meses
     * dentro do periodo do relatorio).
     *
     * @param  Collection<int, BemMovel>  $bens
     * @return Collection<int, Depreciacao>  keyBy patrimonio
     */
    private function depreciacaoAnteriorABaixa(Collection $bens): Collection
    {
        $mesAlvoPorBem = $bens
            ->filter(fn (BemMovel $bem) => $bem->DataBaixa)
            ->mapWithKeys(fn (BemMovel $bem) => [$bem->id => Carbon::parse($bem->DataBaixa)->subMonthNoOverflow()]);

        $linhas = collect();

        // preserveKeys: as chaves sao os ids dos bens, usados no whereIn abaixo.
        foreach ($mesAlvoPorBem->groupBy(fn (Carbon $d) => $d->format('Y-m'), preserveKeys: true) as $doMes) {
            $ref = $doMes->first();

            $linhas = $linhas->concat(
                Depreciacao::query()
                    ->toBase()
                    // Sem o hint o MySQL varre o mes inteiro pelo indice de data (~7 s -> ~3 s).
                    ->forceIndex('idx_mat_depreciacao_patrimonio')
                    ->whereIn('patrimonio', $doMes->keys())
                    // BETWEEN sargable (whereYear/whereMonth nao usa indice em tabela grande).
                    ->whereBetween('data_calculo', [
                        $ref->copy()->startOfMonth()->format('Y-m-d 00:00:00'),
                        $ref->copy()->endOfMonth()->format('Y-m-d 23:59:59'),
                    ])
                    ->get(['patrimonio', 'depreciacao_acumulada', 'valor_liquido_contabil'])
            );
        }

        return $linhas->keyBy('patrimonio');
    }

    private function valorBruto(BemMovel $bem): float
    {
        $incorporacao = $bem->DatadeIncorporacao;

        if ($incorporacao !== null && $incorporacao->lt(Carbon::parse(self::CORTE_HISTORICO))) {
            return (float) $bem->ValordaReavaliacao;
        }

        return (float) $bem->ValorAquisicao;
    }
}
