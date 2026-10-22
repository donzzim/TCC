<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\TCE;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Support\ConsultaImovelPorConta;
use App\Models\egap\Patrimonio\BensImoveis\BemImovel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * TCE - Tabela 13: Demonstrativo Analitico das Entradas e Saidas de Bens Imoveis.
 *
 * Legado: relatorios-prints-queries/tce/tabela-13.php
 *
 * Agrega por conta contabil. Valor de cada imovel:
 *   valorImovel = data_reavaliacao IS NOT NULL ? valor_reavaliado : valor_historico_1a_avaliacao
 *
 * Entradas (data_situacao no periodo, por id_entradasaida):
 *   compras=1, doacao=2, construcao=3, desapropriacao=4
 *   + entrada_obras : SUM(obras.valor) no periodo (imoveis Id_situacao = 1)
 *   + entrada_outras: bloco de ajuste_contabil>=0 + obras do periodo (base da Tabela 12)
 * Saidas (data_baixa no periodo, id_entradasaida = 6):
 *   BUG-LEGADO: alienacao == doacao == perdas (mesma subquery no legado).
 *   + saida_outras : IFNULL(ultimaRea(<inicio).valor, valor_historico) para baixas id_situacao = 1
 *   TotalSaida = alienacao + doacao + perdas + saida_outras  (= 3 * alienacao + saida_outras)
 *
 * BUG-LEGADO: `entrada_reavaliacao` do legado e calculada e nunca usada -> omitida.
 */
final class Tabela13Service implements RelatorioService
{
    use ConsultaImovelPorConta;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicio = Carbon::parse($filtros->inicioHora());
        $fim = Carbon::parse($filtros->fimHora());

        $imoveis = BemImovel::query()->with('planoContasRelacaoRef:id,codigo,titulo')->get();
        if ($imoveis->isEmpty()) {
            return collect();
        }

        $reavaliacoesPorImovel = $this->reavaliacoesPorImovel($imoveis->pluck('Id')->all());
        $obrasPorImovel = $this->obrasPorImovel($imoveis->pluck('Id')->all());

        // Coluna real e `Id_planocontas` (maiuscula) — o atributo minusculo vem
        // null com SELECT *. Usamos o id da relacao ja carregada.
        $contasAtivas = $imoveis->where('Id_situacao', 1)
            ->map(fn ($i) => $i->planoContasRelacaoRef?->id)
            ->filter()
            ->unique()
            ->flip();
        $buckets = [];

        foreach ($imoveis as $imo) {
            $conta = $imo->planoContasRelacaoRef?->id;
            if ($conta === null) {
                continue;
            }
            $buckets[$conta] ??= $this->bucketZero($imo);

            $valorImovel = $imo->data_reavaliacao !== null
                ? (float) $imo->valor_reavaliado
                : (float) $imo->valor_historico_1a_avaliacao;

            $reavaliacoes = $reavaliacoesPorImovel[$imo->Id] ?? collect();
            $obras = $obrasPorImovel[$imo->Id] ?? collect();

            $dataSituacaoNoPeriodo = $imo->data_situacao && Carbon::parse($imo->data_situacao)->betweenIncluded($inicio, $fim);
            $dataBaixaNoPeriodo = $imo->data_baixa && Carbon::parse($imo->data_baixa)->betweenIncluded($inicio, $fim);

            if ($dataSituacaoNoPeriodo) {
                match ((int) $imo->id_entradasaida) {
                    1 => $buckets[$conta]['entrada_compras'] += $valorImovel,
                    2 => $buckets[$conta]['entrada_doacao'] += $valorImovel,
                    3 => $buckets[$conta]['entrada_construcao'] += $valorImovel,
                    4 => $buckets[$conta]['entrada_desapropriacao'] += $valorImovel,
                    default => null,
                };
            }

            if ((int) $imo->Id_situacao === 1) {
                $buckets[$conta]['entrada_obras'] += (float) $obras
                    ->filter(fn ($o) => Carbon::parse($o->data)->betweenIncluded($inicio, $fim))
                    ->sum('valor');
            }

            // entrada_outras / ajuste de saida: base da Tabela 12.
            if ((int) $imo->Id_situacao === 1
                && Carbon::parse($imo->data_aquisicao)->lt($inicio)
                && $this->naoBaixadoAte($imo, $fim)) {
                $reaNoPeriodo = $reavaliacoes->first(fn ($r) => Carbon::parse($r->data_reavaliacao)->betweenIncluded($inicio, $fim));
                $ajuste = (float) ($reaNoPeriodo->ajuste_contabil ?? 0);
                $obrasEntrada = (float) $obras->filter(fn ($o) => Carbon::parse($o->data)->betweenIncluded($inicio, $fim))->sum('valor');

                $buckets[$conta]['entrada_outras'] += ($ajuste >= 0 ? $ajuste : 0.0) + $obrasEntrada;
                $buckets[$conta]['ajuste_saida'] += $ajuste < 0 ? $ajuste * -1 : 0.0;
            }

            if ($dataBaixaNoPeriodo && (int) $imo->id_entradasaida === 6) {
                $buckets[$conta]['saida_alienacao'] += $valorImovel;

                if ((int) $imo->Id_situacao === 1) {
                    $ultimaReaAntes = $reavaliacoes->first(fn ($r) => Carbon::parse($r->data_reavaliacao)->lt($inicio));
                    $buckets[$conta]['saida_outras'] += (float) ($ultimaReaAntes->valor_reavaliacao ?? $imo->valor_historico_1a_avaliacao);
                }
            }
        }

        return collect($buckets)
            ->filter(fn (array $b, $conta) => $contasAtivas->has($conta))
            ->map(function (array $b): array {
                $entradaConstrucao = $b['entrada_construcao'] + $b['entrada_obras'];
                $totalEntrada = $b['entrada_compras'] + $b['entrada_doacao'] + $entradaConstrucao
                    + $b['entrada_desapropriacao'] + $b['entrada_outras'];

                // BUG-LEGADO: alienacao == doacao == perdas.
                $alienacao = $b['saida_alienacao'];
                $saidaOutras = $b['ajuste_saida'] + $b['saida_outras'];
                $totalSaida = $alienacao + $alienacao + $alienacao + $saidaOutras;

                return [
                    'conta_contabil' => $b['conta_contabil'],
                    'descricao' => $b['descricao'],
                    'entrada_compra' => $b['entrada_compras'],
                    'entrada_doacao' => $b['entrada_doacao'],
                    'entrada_construcao' => $entradaConstrucao,
                    'entrada_desapropriacao' => $b['entrada_desapropriacao'],
                    'entrada_outras' => $b['entrada_outras'],
                    'total_entrada' => $totalEntrada,
                    'saida_alienacao' => $alienacao,
                    'saida_doacao' => $alienacao,
                    'saida_perdas' => $alienacao,
                    'saida_outras' => $saidaOutras,
                    'total_saida' => $totalSaida,
                ];
            })
            ->sortBy('conta_contabil')
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function bucketZero(BemImovel $imo): array
    {
        return [
            'conta_contabil' => $imo->planoContasRelacaoRef?->codigo,
            'descricao' => $imo->planoContasRelacaoRef?->titulo,
            'entrada_compras' => 0.0,
            'entrada_doacao' => 0.0,
            'entrada_construcao' => 0.0,
            'entrada_desapropriacao' => 0.0,
            'entrada_obras' => 0.0,
            'entrada_outras' => 0.0,
            'ajuste_saida' => 0.0,
            'saida_alienacao' => 0.0,
            'saida_outras' => 0.0,
        ];
    }
}
