<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensImoveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensImoveis\BemImovel;
use App\Models\egap\Patrimonio\BensImoveis\Depreciacao;
use App\Models\egap\Patrimonio\BensImoveis\Obra;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Relatorio "Inventario dos Bens Imoveis".
 *
 * Legado: relatorios-prints-queries/bens-imoveis/inventario-dos-bens-imoveis.php
 *
 *   INNER JOIN mat_planocontas
 *   LEFT JOIN (imo_depreciacao no mes do termino, GROUP BY Id_imovel) dep
 *   LEFT JOIN (SUM(imo_obras.valor) WHERE data <= termino, GROUP BY id_imovel) obras
 *   WHERE (p.data_baixa IS NULL OR p.data_baixa = '0000-00-00 00:00:00') {AND conta}
 *   ORDER BY pla.codigo, pla.titulo, p.num_registro
 *
 * Projecao:
 *   ValorReavaliado      = IFNULL(valor_reavaliado,0) + IFNULL(valor_obra,0)
 *   DepreciacaoAcumulada = IFNULL(dep.depreciacao_acumulada, p.depreciacao_acumulada)
 *   Valor                = IFNULL(dep.valor_liquido_contabil,
 *                                 IF(p.valor_liquido_contabil > 0, p.valor_liquido_contabil, ValorReavaliado))
 *                          + IFNULL(valor_obra,0)
 *
 * BUG-LEGADO: o filtro de conta usa `p.ContaContabil` (alias/coluna inexistente
 * em imo_imovel). Aqui aplicamos a coluna correta `id_planocontas`.
 */
final class InventarioBensImoveisService implements RelatorioService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $termino = $filtros->terminoObrigatorio();
        $terminoData = $termino->format('Y-m-d');

        $imoveis = BemImovel::query()
            ->with('planoContasRelacaoRef:id,codigo,titulo')
            ->where(fn ($q) => $q->whereNull('data_baixa')->orWhere('data_baixa', '0000-00-00 00:00:00'))
            ->when($filtros->conta, fn ($q) => $q->where('id_planocontas', $filtros->conta))
            ->get();

        if ($imoveis->isEmpty()) {
            return collect();
        }

        $ids = $imoveis->pluck('Id')->all();

        $depPorImovel = Depreciacao::query()
            ->whereIn('Id_imovel', $ids)
            ->whereYear('data_calculo', $termino->year)
            ->whereMonth('data_calculo', $termino->month)
            ->get(['Id_imovel', 'depreciacao_acumulada', 'valor_liquido_contabil'])
            ->keyBy('Id_imovel');

        $obraPorImovel = Obra::query()
            ->whereIn('id_imovel', $ids)
            ->whereDate('data', '<=', $terminoData)
            ->groupBy('id_imovel')
            ->select('id_imovel')
            ->selectRaw('SUM(valor) as valor_obra')
            ->pluck('valor_obra', 'id_imovel');

        return $imoveis
            ->map(function (BemImovel $imo) use ($depPorImovel, $obraPorImovel): array {
                $dep = $depPorImovel[$imo->Id] ?? null;
                $valorObra = (float) ($obraPorImovel[$imo->Id] ?? 0);

                $valorReavaliado = (float) ($imo->valor_reavaliado ?? 0) + $valorObra;

                $valorContabilBase = $dep->valor_liquido_contabil
                    ?? (((float) $imo->valor_liquido_contabil > 0) ? (float) $imo->valor_liquido_contabil : $valorReavaliado);

                return [
                    'conta_titulo' => $imo->planoContasRelacaoRef?->titulo,
                    'conta_codigo' => $imo->planoContasRelacaoRef?->codigo,
                    'num_registro' => $imo->num_registro,
                    'descricao' => $imo->descricao,
                    'inscricao_generica' => $imo->inscricao_generica,
                    'inscricao_imobiliaria' => $imo->inscricao_imobiliaria,
                    'data_aquisicao' => $this->data($imo->data_incorporacao),
                    'valor_reavaliado' => $valorReavaliado,
                    'depreciacao_acumulada' => (float) ($dep->depreciacao_acumulada ?? $imo->depreciacao_acumulada ?? 0),
                    'valor' => (float) $valorContabilBase + $valorObra,
                    'localizacao' => $this->endereco($imo),
                    'valor_liquido_contabil' => (float) $imo->valor_liquido_contabil,
                ];
            })
            // Legado: ORDER BY pla.codigo, pla.titulo, p.num_registro
            ->sort(fn ($a, $b) => [(string) $a['conta_codigo'], (string) $a['conta_titulo'], (string) $a['num_registro']]
                <=> [(string) $b['conta_codigo'], (string) $b['conta_titulo'], (string) $b['num_registro']])
            ->values();
    }

    private function data(mixed $valor): ?string
    {
        if (blank($valor) || $valor === '0000-00-00' || $valor === '0000-00-00 00:00:00') {
            return null;
        }

        return Carbon::parse($valor)->format('d/m/Y');
    }

    private function endereco(BemImovel $imo): string
    {
        // Legado: CONCAT_WS(', ', end_logradouro, IF(end_numero<>'null',end_numero,'s/n'),
        //                          IF(end_bairro<>'null',end_bairro,'Centro'), end_cidade, end_estado)
        $numero = ($imo->end_numero === null || $imo->end_numero === 'null') ? 's/n' : $imo->end_numero;
        $bairro = ($imo->end_bairro === null || $imo->end_bairro === 'null') ? 'Centro' : $imo->end_bairro;

        return collect([$imo->end_logradouro, $numero, $bairro, $imo->end_cidade, $imo->end_estado])
            ->filter(fn ($p) => $p !== null)
            ->join(', ');
    }
}
