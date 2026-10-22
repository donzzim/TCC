<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\TCE;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Support\ConsultaImovelPorConta;
use App\Models\egap\Patrimonio\BensImoveis\Depreciacao;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * TCE - Tabela 12: Resumo do Inventario dos Bens Imoveis.
 *
 * Legado: relatorios-prints-queries/tce/tabela-12.php
 *
 * Agrega por conta contabil (imo_imovel.id_planocontas), imoveis com Id_situacao = 1.
 *   SaldoAnterior = SUM( IFNULL(ultimaRea(<inicio).valor, valor_historico_1a_avaliacao)
 *                        + SUM(obras entre ultimaRea e inicio) )
 *                   para imoveis com data_aquisicao < inicio e (data_baixa >= termino | zero/null)
 *   Entradas      = SUM( IF(reaNoPeriodo.ajuste_contabil >= 0, ajuste, 0) + SUM(obras no periodo) )
 *   Saidas        = SUM( IF(ajuste < 0, -ajuste, 0) )  [de Entradas]
 *                   + SUM( IFNULL(ultimaRea(<inicio).valor, valor_historico) ) para baixas no periodo
 *   Depreciacao   = SUM(imo_depreciacao.depreciacao_acumulada) no mes do termino
 *   SaldoBruto    = SaldoAnterior + Entradas - Saidas
 *   SaldoAtual    = SaldoBruto - Depreciacao
 *
 * BUG-LEGADO: a subquery `saidas` do legado tem um join de obras nao
 * deterministico (ultima_obra x reavaliacao). Aqui a parcela de obra das saidas
 * e desconsiderada (contribuicao = valor reavaliado/historico).
 */
final class Tabela12Service implements RelatorioService
{
    use ConsultaImovelPorConta;

    /**
     * @return Collection<int, array{
     *     conta_contabil: ?string, descricao: ?string,
     *     saldo_anterior: float, entradas: float, saidas: float,
     *     saldo_bruto: float, depreciacao_acumulada: float, saldo_atual: float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $termino = $filtros->terminoObrigatorio();
        $inicio = Carbon::parse($filtros->inicioHora());
        $fim = Carbon::parse($filtros->fimHora());

        $imoveis = $this->imoveisAtivos($filtros);
        if ($imoveis->isEmpty()) {
            return collect();
        }

        $reavaliacoesPorImovel = $this->reavaliacoesPorImovel($imoveis->pluck('Id')->all());
        $obrasPorImovel = $this->obrasPorImovel($imoveis->pluck('Id')->all());
        $depreciacaoPorConta = $this->depreciacaoPorConta($termino->year, $termino->month);

        $porConta = [];

        foreach ($imoveis as $imo) {
            // Coluna real e `Id_planocontas` (maiuscula) — o atributo minusculo
            // vem null quando o model e carregado com SELECT *. Usamos o id da
            // relacao ja carregada.
            $conta = $imo->planoContasRelacaoRef?->id;
            if ($conta === null) {
                continue;
            }
            $porConta[$conta] ??= [
                'conta_contabil' => $imo->planoContasRelacaoRef?->codigo,
                'descricao' => $imo->planoContasRelacaoRef?->titulo,
                'saldo_anterior' => 0.0,
                'entradas' => 0.0,
                'ajuste_saida' => 0.0,
                'saidas_valor' => 0.0,
            ];

            $reavaliacoes = ($reavaliacoesPorImovel[$imo->Id] ?? collect());
            $obras = ($obrasPorImovel[$imo->Id] ?? collect());

            $ultimaReaAntes = $reavaliacoes->first(fn ($r) => Carbon::parse($r->data_reavaliacao)->lt($inicio));
            $reaNoPeriodo = $reavaliacoes->first(fn ($r) => Carbon::parse($r->data_reavaliacao)->betweenIncluded($inicio, $fim));

            // --- Saldo anterior (imoveis adquiridos antes do inicio, nao baixados ate o termino) ---
            if (Carbon::parse($imo->data_aquisicao)->lt($inicio) && $this->naoBaixadoAte($imo, $fim)) {
                $valorBase = (float) ($ultimaReaAntes->valor_reavaliacao ?? $imo->valor_historico_1a_avaliacao);
                $obraSaldo = $ultimaReaAntes !== null
                    ? (float) $obras->filter(fn ($o) => Carbon::parse($o->data)->betweenIncluded(Carbon::parse($ultimaReaAntes->data_reavaliacao), $inicio))->sum('valor')
                    : 0.0;
                $porConta[$conta]['saldo_anterior'] += $valorBase + $obraSaldo;

                // --- Entradas / ajuste de saida (mesma base de imoveis) ---
                $ajuste = (float) ($reaNoPeriodo->ajuste_contabil ?? 0);
                $obrasEntrada = (float) $obras->filter(fn ($o) => Carbon::parse($o->data)->betweenIncluded($inicio, $fim))->sum('valor');
                $porConta[$conta]['entradas'] += ($ajuste >= 0 ? $ajuste : 0.0) + $obrasEntrada;
                $porConta[$conta]['ajuste_saida'] += $ajuste < 0 ? $ajuste * -1 : 0.0;
            }

            // --- Saidas (baixas no periodo) ---
            if ($imo->data_baixa && Carbon::parse($imo->data_baixa)->betweenIncluded($inicio, $fim)) {
                $porConta[$conta]['saidas_valor'] += (float) ($ultimaReaAntes->valor_reavaliacao ?? $imo->valor_historico_1a_avaliacao);
            }
        }

        return collect($porConta)
            ->map(function (array $c) use ($depreciacaoPorConta): array {
                $saidas = $c['ajuste_saida'] + $c['saidas_valor'];
                $saldoBruto = $c['saldo_anterior'] + $c['entradas'] - $saidas;
                $depAcum = (float) ($depreciacaoPorConta[$c['conta_contabil']] ?? 0);

                return [
                    'conta_contabil' => $c['conta_contabil'],
                    'descricao' => $c['descricao'],
                    'saldo_anterior' => $c['saldo_anterior'],
                    'entradas' => $c['entradas'],
                    'saidas' => $saidas,
                    'saldo_bruto' => $saldoBruto,
                    'depreciacao_acumulada' => $depAcum,
                    'saldo_atual' => $saldoBruto - $depAcum,
                ];
            })
            ->sortBy('conta_contabil')
            ->values();
    }

    /**
     * SUM(imo_depreciacao.depreciacao_acumulada) no mes do termino, por codigo de conta.
     *
     * @return Collection<string, float>  keyBy codigo da conta
     */
    private function depreciacaoPorConta(int $ano, int $mes): Collection
    {
        return Depreciacao::query()
            ->from('egap_imo_depreciacao as d')
            ->join('egap_imo_imovel as imo', 'imo.Id', '=', 'd.Id_imovel')
            ->join('egap_mat_planocontas as pc', 'imo.id_planocontas', '=', 'pc.id')
            ->where('imo.Id_situacao', 1)
            ->whereYear('d.data_calculo', $ano)
            ->whereMonth('d.data_calculo', $mes)
            ->groupBy('pc.codigo')
            ->select('pc.codigo')
            ->selectRaw('SUM(d.depreciacao_acumulada) as valor')
            ->pluck('valor', 'codigo')
            ->map(fn ($v) => (float) $v);
    }
}
