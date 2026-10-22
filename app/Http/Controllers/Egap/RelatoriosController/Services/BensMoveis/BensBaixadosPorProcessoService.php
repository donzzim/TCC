<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensMoveis\Baixa;
use Illuminate\Support\Collection;

/**
 * Relatorio "Bens Baixados por Processo".
 *
 * Legado: relatorios-prints-queries/bens-moveis/bens-baixados-por-processo.php
 *
 *   SELECT mat_patrimonio.ProcessoBaixa, COUNT(mat_itembaixa.id) TotalBem,
 *          SUM(mat_patrimonio.ValorAquisicao) TotalAquisicao,
 *          SUM(mat_patrimonio.ValordaReavaliacao) TotalReavaliacao
 *   FROM mat_baixa
 *     INNER JOIN mat_itembaixa ON mat_baixa.id = mat_itembaixa.id_baixa
 *     INNER JOIN mat_patrimonio ON mat_itembaixa.id_bem = mat_patrimonio.id
 *   WHERE mat_baixa.DataBaixa BETWEEN :inicio AND :termino
 *   GROUP BY mat_patrimonio.ProcessoBaixa
 *   ORDER BY mat_patrimonio.ProcessoBaixa
 */
final class BensBaixadosPorProcessoService implements RelatorioService
{
    /**
     * @return Collection<int, array{
     *     processo_baixa: ?string, total_bem: int, total_aquisicao: float, total_reavaliacao: float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicio = $filtros->inicioHora();
        $termino = $filtros->fimHora();

        return Baixa::query()
            ->from('egap_mat_baixa')
            ->join('egap_mat_itembaixa', 'egap_mat_baixa.id', '=', 'egap_mat_itembaixa.id_baixa')
            ->join('egap_mat_patrimonio', 'egap_mat_itembaixa.id_bem', '=', 'egap_mat_patrimonio.id')
            ->whereBetween('egap_mat_baixa.DataBaixa', [$inicio, $termino])
            ->groupBy('egap_mat_patrimonio.ProcessoBaixa')
            ->orderBy('egap_mat_patrimonio.ProcessoBaixa')
            ->select('egap_mat_patrimonio.ProcessoBaixa as processo_baixa')
            ->selectRaw('COUNT(egap_mat_itembaixa.id) as total_bem')
            ->selectRaw('SUM(egap_mat_patrimonio.ValorAquisicao) as total_aquisicao')
            ->selectRaw('SUM(egap_mat_patrimonio.ValordaReavaliacao) as total_reavaliacao')
            ->get()
            ->map(fn ($r): array => [
                'processo_baixa' => $r->processo_baixa,
                'total_bem' => (int) $r->total_bem,
                'total_aquisicao' => (float) $r->total_aquisicao,
                'total_reavaliacao' => (float) $r->total_reavaliacao,
            ]);
    }
}
