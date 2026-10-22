<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensImoveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensImoveis\BemImovel;
use App\Models\egap\Patrimonio\BensImoveis\Obra;
use App\Models\egap\Patrimonio\BensImoveis\Reavaliacao;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Relatorio "Saldo Anterior de Imoveis" (teste do saldo anterior).
 *
 * Legado: relatorios-prints-queries/bens-imoveis/relatorio-saldo-anterior-imoveis.php
 *
 * WHERE (efetivo): Id_situacao = 1 AND data_aquisicao <= termino
 *   AND (data_baixa >= termino OR data_baixa = '0000-00-00 00:00:00' OR data_baixa IS NULL)
 *
 * Por imovel:
 *   - ultima_rea = reavaliacao mais recente com data_reavaliacao <= termino
 *   - rea        = ultima_rea, SO SE a data dela estiver no periodo [inicio, termino]
 *   - data_reavaliacao (@datareavaliacao) = data de `rea` (null se nao ha rea no periodo)
 *   - valor_obra  = SUM(obras.valor) na janela:
 *        sem reavaliacao no periodo -> data <= termino
 *        com reavaliacao no periodo -> data BETWEEN data_reavaliacao AND termino
 *   - saldo_anterior = (@datareavaliacao != null ? valor_reavaliacao : valor_historico_1a_avaliacao) + valor_obra
 *   - valorobra_entrada = SUM(obras.valor) com data BETWEEN inicio AND termino
 *   - ajuste_contabil_entrada / _saida = split de rea.ajuste_contabil por sinal
 *   ORDER BY imo.id
 */
final class SaldoAnteriorImoveisService implements RelatorioService
{
    private const ZERO_DATE = '0000-00-00 00:00:00';

    /**
     * @return Collection<int, array{
     *     id: int, descricao: ?string, saldo_anterior: float, valorobra_entrada: float,
     *     ajuste_contabil_entrada: float, ajuste_contabil_saida: float,
     *     data_aquisicao: ?string, valor_historico: float, data_baixa: ?string,
     *     data_situacao: ?string, data_reavaliacao: ?string, valor_reavaliacao: float, situacao: ?string
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicio = Carbon::parse($filtros->inicioHora());   // Y-m-d 00:00:01
        $termino = Carbon::parse($filtros->fimHora());     // Y-m-d 23:59:59

        $imoveis = BemImovel::query()
            ->with('situacaoRelacaoRef')
            ->where('Id_situacao', 1)
            ->where('data_aquisicao', '<=', $termino->format('Y-m-d H:i:s'))
            ->where(fn ($q) => $q
                ->where('data_baixa', '>=', $termino->format('Y-m-d H:i:s'))
                ->orWhere('data_baixa', self::ZERO_DATE)
                ->orWhereNull('data_baixa'))
            ->orderBy('Id')
            ->get();

        if ($imoveis->isEmpty()) {
            return collect();
        }

        $ids = $imoveis->pluck('Id')->all();

        // Reavaliacao mais recente <= termino, por imovel.
        $ultimaReaPorImovel = Reavaliacao::query()
            ->whereIn('Id_imovel', $ids)
            ->where('data_reavaliacao', '<=', $termino->format('Y-m-d H:i:s'))
            ->orderBy('Id_imovel')
            ->orderByDesc('data_reavaliacao')
            ->get(['Id_imovel', 'data_reavaliacao', 'valor_reavaliacao', 'ajuste_contabil'])
            ->groupBy('Id_imovel')
            ->map(fn (Collection $g) => $g->first());

        $obrasPorImovel = Obra::query()
            ->whereIn('id_imovel', $ids)
            ->get(['id_imovel', 'valor', 'data'])
            ->groupBy('id_imovel');

        return $imoveis->map(function (BemImovel $imo) use ($inicio, $termino, $ultimaReaPorImovel, $obrasPorImovel): array {
            $ultimaRea = $ultimaReaPorImovel[$imo->Id] ?? null;

            $reaNoPeriodo = null;
            if ($ultimaRea !== null && $ultimaRea->data_reavaliacao) {
                $dataRea = Carbon::parse($ultimaRea->data_reavaliacao);
                if ($dataRea->betweenIncluded($inicio, $termino)) {
                    $reaNoPeriodo = $ultimaRea;
                }
            }

            $dataReavaliacao = ($reaNoPeriodo !== null && ! $this->isZeroDate($reaNoPeriodo->data_reavaliacao))
                ? Carbon::parse($reaNoPeriodo->data_reavaliacao)
                : null;

            $valorReavaliacao = (float) ($reaNoPeriodo->valor_reavaliacao ?? 0);
            $obras = $obrasPorImovel[$imo->Id] ?? collect();

            $valorObra = (float) $obras
                ->filter(function ($o) use ($dataReavaliacao, $termino): bool {
                    $data = Carbon::parse($o->data);

                    return $dataReavaliacao === null
                        ? $data->lte($termino)
                        : $data->betweenIncluded($dataReavaliacao, $termino);
                })
                ->sum('valor');

            $valorObraEntrada = (float) $obras
                ->filter(fn ($o) => Carbon::parse($o->data)->betweenIncluded($inicio, $termino))
                ->sum('valor');

            $ajuste = (float) ($reaNoPeriodo->ajuste_contabil ?? 0);

            $saldoBase = $dataReavaliacao !== null
                ? $valorReavaliacao
                : (float) $imo->valor_historico_1a_avaliacao;

            return [
                'id' => (int) $imo->Id,
                'descricao' => $imo->descricao,
                'saldo_anterior' => $saldoBase + $valorObra,
                'valorobra_entrada' => $valorObraEntrada,
                'ajuste_contabil_entrada' => $ajuste >= 0 ? $ajuste : 0.0,
                'ajuste_contabil_saida' => $ajuste < 0 ? $ajuste * -1 : 0.0,
                'data_aquisicao' => $this->data($imo->data_aquisicao),
                'valor_historico' => (float) $imo->valor_historico_1a_avaliacao,
                'data_baixa' => $this->data($imo->data_baixa),
                'data_situacao' => $this->data($imo->data_situacao),
                'data_reavaliacao' => $dataReavaliacao?->format('Y-m-d'),
                'valor_reavaliacao' => $valorReavaliacao,
                'situacao' => $imo->situacaoRelacaoRef?->Descricao,
            ];
        })->values();
    }

    private function isZeroDate(mixed $valor): bool
    {
        return $valor === null || $valor === '0000-00-00' || $valor === self::ZERO_DATE;
    }

    private function data(mixed $valor): ?string
    {
        if ($this->isZeroDate($valor) || blank($valor)) {
            return null;
        }

        return Carbon::parse($valor)->format('d/m/Y');
    }
}
