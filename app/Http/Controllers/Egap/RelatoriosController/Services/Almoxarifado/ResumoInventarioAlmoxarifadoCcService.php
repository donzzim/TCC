<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use App\Services\Egap\CentroCustoSetor;
use Illuminate\Support\Collection;

/**
 * Relatorio "Resumo do Inventario do Almoxarifado por Centro de Custo".
 *
 * Legado: relatorios-prints-queries/almoxarifado/resumo-inventario-almoxarifado-centro-custo.php
 *
 * Para cada centro de custo (todos, ou o filtrado), o legado monta uma tabela
 * conta x produto com:
 *   - SaldoAnterior     = SUM(valor_total_estoque) da ultima mov. antes do inicio
 *   - Entradas          = SUM(valor_total) tipo=1 no periodo
 *   - Saidas            = SUM(valor_total) tipo=2 no periodo
 *   - SaidasAcumuladas  = SUM(valor_total) tipo=2 entre 01/01 do ano e o inicio
 *   - SaldoAtual        = SaldoAnterior + Entradas - Saidas
 * escopo do CC: est.id_setor IN (setores com aquele centrocusto), dr.id_tipo_material <> 'P'.
 *
 * `gerar()` devolve o bloco de DETALHE (linhas conta x produto, com o CC).
 * `resumos()` devolve os 3 blocos de totalizacao (por CC, por conta, por item
 * patrimonial) a partir do detalhe — sem repetir consultas.
 */
final class ResumoInventarioAlmoxarifadoCcService implements RelatorioService
{
    /**
     * @return Collection<int, array{
     *     centro_custo_codigo: string, centro_custo_descricao: ?string,
     *     conta_contabil: ?string, produto: ?string, item_patrimonial: ?string, descricao: ?string,
     *     saldo_anterior: float, entradas: float, saidas: float, saidas_acumuladas: float, saldo_atual: float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicioData = $filtros->inicioData();
        $inicio = $filtros->inicioHora();
        $termino = $filtros->fimHora();
        $inicioAno = $filtros->inicioAno();

        // O legado roda as consultas uma vez por centro de custo (~80 x 5). Aqui
        // cada consulta roda uma vez, agrupada por setor, e o PHP soma por centro.
        $setorParaCentro = CentroCustoSetor::mapaSetores($filtros->centroCusto);

        if ($setorParaCentro === []) {
            return collect();
        }

        $setorIds = array_keys($setorParaCentro);

        $tuplas = $this->porCentro($this->tuplasContaProduto($setorIds), $setorParaCentro);
        $saldoAnterior = $this->porCentro($this->saldoAnterior($setorIds, $inicioData), $setorParaCentro);
        $entradas = $this->porCentro($this->somaPeriodo($setorIds, 1, $inicio, $termino), $setorParaCentro);
        $saidas = $this->porCentro($this->somaPeriodo($setorIds, 2, $inicio, $termino), $setorParaCentro);
        $saidasAcumuladas = $this->porCentro($this->somaPeriodo($setorIds, 2, $inicioAno, $inicio), $setorParaCentro);

        return CentroCustoSetor::listar($filtros->centroCusto)
            // tuplasContaProduto() já vem ordenada por conta/produto; porCentro() preserva a ordem.
            ->flatMap(fn (object $centro) => collect($tuplas[$centro->codigo] ?? [])
                ->map(function (object $t, string $chave) use ($centro, $saldoAnterior, $entradas, $saidas, $saidasAcumuladas): array {
                    $sa = $saldoAnterior[$centro->codigo][$chave]->valor ?? 0.0;
                    $ent = $entradas[$centro->codigo][$chave]->valor ?? 0.0;
                    $sai = $saidas[$centro->codigo][$chave]->valor ?? 0.0;

                    return [
                        'centro_custo_codigo' => (string) $centro->codigo,
                        'centro_custo_descricao' => $centro->descricao,
                        'conta_contabil' => $t->conta_contabil,
                        'produto' => $t->produto,
                        'item_patrimonial' => $t->item_patrimonial,
                        'descricao' => $t->descricao,
                        'saldo_anterior' => $sa,
                        'entradas' => $ent,
                        'saidas' => $sai,
                        'saidas_acumuladas' => $saidasAcumuladas[$centro->codigo][$chave]->valor ?? 0.0,
                        'saldo_atual' => $sa + $ent - $sai,
                    ];
                })
                ->values())
            ->values();
    }

    /**
     * Reagrupa linhas (id_setor, id_conta, id_produto) em
     * [codigo do CC]["id_conta|id_produto"], somando `valor` entre setores.
     *
     * @param  Collection<int, object>  $linhas
     * @param  array<int, string>  $setorParaCentro
     * @return array<string, array<string, object>>
     */
    private function porCentro(Collection $linhas, array $setorParaCentro): array
    {
        $agrupado = [];

        foreach ($linhas as $linha) {
            $centro = $setorParaCentro[(int) $linha->id_setor] ?? null;

            if ($centro === null) {
                continue;
            }

            $chave = $linha->id_conta.'|'.$linha->id_produto;

            if (! isset($agrupado[$centro][$chave])) {
                $agrupado[$centro][$chave] = clone $linha;
                $agrupado[$centro][$chave]->valor = (float) ($linha->valor ?? 0);

                continue;
            }

            $agrupado[$centro][$chave]->valor += (float) ($linha->valor ?? 0);
        }

        return $agrupado;
    }

    /**
     * Totalizacoes derivadas do detalhe de gerar().
     *
     * @param  Collection<int, array<string, mixed>>  $detalhe
     * @return array{
     *     por_centro_custo: list<array<string, mixed>>,
     *     por_conta: list<array<string, mixed>>,
     *     por_item_patrimonial: list<array<string, mixed>>
     * }
     */
    public function resumos(Collection $detalhe): array
    {
        $medidas = ['saldo_anterior', 'entradas', 'saidas', 'saldo_atual', 'saidas_acumuladas'];
        $somar = static function (Collection $linhas) use ($medidas): array {
            return collect($medidas)
                ->mapWithKeys(fn (string $m) => [$m => (float) $linhas->sum($m)])
                ->all();
        };

        $porCentroCusto = $detalhe
            ->groupBy('centro_custo_codigo')
            ->map(fn (Collection $linhas) => [
                'codigo' => $linhas->first()['centro_custo_codigo'],
                'centro_custo' => $linhas->first()['centro_custo_descricao'],
                ...$somar($linhas),
            ])
            ->values()
            ->all();

        $porConta = $detalhe
            ->groupBy('conta_contabil')
            ->map(fn (Collection $linhas) => [
                'conta_contabil' => $linhas->first()['conta_contabil'],
                'produto' => $linhas->first()['produto'],
                'descricao' => $linhas->first()['descricao'],
                ...$somar($linhas),
            ])
            ->values()
            ->all();

        $porItemPatrimonial = $detalhe
            ->groupBy('item_patrimonial')
            ->map(fn (Collection $linhas) => [
                'item_patrimonial' => $linhas->first()['item_patrimonial'],
                ...$somar($linhas),
            ])
            ->values()
            ->all();

        return [
            'por_centro_custo' => $porCentroCusto,
            'por_conta' => $porConta,
            'por_item_patrimonial' => $porItemPatrimonial,
        ];
    }

    /**
     * Combos setor x conta contabil x produto com estoque nos setores informados.
     *
     * @param  list<int>  $setorIds
     * @return Collection<int, object>
     */
    private function tuplasContaProduto(array $setorIds): Collection
    {
        return MovimentacaoEstoque::query()
            ->toBase()
            ->from('egap_alm_estoque as est')
            ->join('egap_mat_descricaodetalhada as dd', 'est.material', '=', 'dd.id')
            ->join('egap_mat_descricaoresumida as dr', 'dd.descricao_resumida', '=', 'dr.id')
            ->join('egap_mat_planocontas as pc', 'dr.ContaContabil', '=', 'pc.id')
            ->join('egap_mat_produtos as pr', 'dr.id_produto', '=', 'pr.id')
            ->where('dr.id_tipo_material', '<>', 'P')
            ->whereIn('est.id_setor', $setorIds)
            ->groupBy('est.id_setor', 'pc.id', 'pr.id', 'pc.codigo', 'pr.CodigodaClasse', 'pr.item_patrimonial', 'pr.DescricaodaClasse')
            ->orderBy('pc.codigo')
            ->orderBy('pr.CodigodaClasse')
            ->get([
                'est.id_setor',
                'pc.id as id_conta',
                'pr.id as id_produto',
                'pc.codigo as conta_contabil',
                'pr.CodigodaClasse as produto',
                'pr.item_patrimonial',
                'pr.DescricaodaClasse as descricao',
            ]);
    }

    /**
     * SUM(valor_total_estoque) da ultima movimentacao (antes do inicio) por
     * setor x conta x produto.
     *
     * @param  list<int>  $setorIds
     * @return Collection<int, object>
     */
    private function saldoAnterior(array $setorIds, string $inicioData): Collection
    {
        // Legado: subquery `ult` NAO filtra por setor (so a query externa filtra).
        $idsUltima = MovimentacaoEstoque::query()
            ->where('date_time', '<', $inicioData)
            ->groupBy('material')
            ->selectRaw('MAX(id) as id');

        return MovimentacaoEstoque::query()
            ->toBase()
            ->from('egap_alm_estoque as est')
            // joinSub em vez de whereIn(subquery): o MySQL materializa o MAX(id) uma vez (2,2 s -> 0,8 s).
            ->joinSub($idsUltima, 'ult', 'ult.id', '=', 'est.id')
            ->join('egap_mat_descricaodetalhada as dd', 'est.material', '=', 'dd.id')
            ->join('egap_mat_descricaoresumida as dr', 'dd.descricao_resumida', '=', 'dr.id')
            ->whereIn('est.id_setor', $setorIds)
            ->groupBy('est.id_setor', 'dr.ContaContabil', 'dr.id_produto')
            ->select(['est.id_setor', 'dr.ContaContabil as id_conta', 'dr.id_produto as id_produto'])
            ->selectRaw('SUM(est.valor_total_estoque) as valor')
            ->get();
    }

    /**
     * SUM(valor_total) por setor x conta x produto, para um tipo de
     * movimentacao entre duas datas.
     *
     * @param  list<int>  $setorIds
     * @return Collection<int, object>
     */
    private function somaPeriodo(array $setorIds, int $tipo, string $de, string $ate): Collection
    {
        return MovimentacaoEstoque::query()
            ->toBase()
            ->from('egap_alm_estoque as est')
            ->join('egap_mat_descricaodetalhada as dd', 'est.material', '=', 'dd.id')
            ->join('egap_mat_descricaoresumida as dr', 'dd.descricao_resumida', '=', 'dr.id')
            ->where('est.tipo_movimentacao', $tipo)
            ->whereBetween('est.date_time', [$de, $ate])
            ->whereIn('est.id_setor', $setorIds)
            ->groupBy('est.id_setor', 'dr.ContaContabil', 'dr.id_produto')
            ->select(['est.id_setor', 'dr.ContaContabil as id_conta', 'dr.id_produto as id_produto'])
            ->selectRaw('SUM(est.valor_total) as valor')
            ->get();
    }
}
