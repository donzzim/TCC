<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use Illuminate\Support\Collection;

/**
 * Relatorio "Inventario dos Bens Moveis - Detalhado".
 *
 * Legado: relatorios-prints-queries/bens-moveis/inventario-bens-moveis-detalhado.php
 *
 * Igual ao "Inventario dos Bens Moveis", porem SEM o join de mat_depreciacao e
 * SEM a obrigatoriedade da conta contabil.
 *
 *   SELECT pla.titulo, pla.codigo, p.NumPatrimonio, p.Descricao, p.DatadeIncorporacao,
 *          IFNULL(p.ValorAquisicao,0) ValorAquisicao,
 *          IFNULL(p.ValordaReavaliacao,0) ValorAjustado,
 *          IF(IFNULL(p.ValordaReavaliacao,0) > 0, p.ValordaReavaliacao, p.ValorAquisicao) ValorAtual,
 *          se.Setor
 *   FROM mat_patrimonio p INNER JOIN mat_planocontas pla / mat_setores se
 *   WHERE p.SituacaoBem IN (1,7)
 *     AND p.DatadeIncorporacao <= :termino
 *     AND YEAR(p.DatadaReavaliacao) <= YEAR(:termino)
 *     {AND p.ContaContabil = :conta} {AND p.situacao_contabil = :situacao}
 *   ORDER BY pla.titulo, p.NumPatrimonio
 */
final class InventarioBensMoveisDetalhadoService implements RelatorioService
{
    /**
     * @return Collection<int, array{
     *     conta_titulo: ?string, conta_codigo: ?string, num_patrimonio: mixed, descricao: ?string,
     *     data_aquisicao: ?string, valor_aquisicao: float, valor_ajustado: float,
     *     valor_atual: float, setor: ?string
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $terminoData = $filtros->fimData();
        $anoTermino = $filtros->anoDoTermino();

        // ~100 mil linhas: joins + ORDER BY no banco e sem hidratar models
        // (com Eloquent + sort em PHP o relatorio levava ~27 s).
        return BemMovel::query()
            ->toBase()
            ->from('egap_mat_patrimonio as p')
            ->leftJoin('egap_mat_planocontas as pla', 'pla.id', '=', 'p.ContaContabil')
            ->leftJoin('setores as se', 'se.id', '=', 'p.Setor')
            ->whereIn('p.SituacaoBem', [1, 7])
            ->whereDate('p.DatadeIncorporacao', '<=', $terminoData)
            ->whereYear('p.DatadaReavaliacao', '<=', $anoTermino)
            ->when($filtros->conta, fn ($q) => $q->where('p.ContaContabil', $filtros->conta))
            ->when($filtros->situacaoContabil, fn ($q) => $q->where('p.situacao_contabil', $filtros->situacaoContabil))
            // Legado: ORDER BY pla.titulo, p.NumPatrimonio
            ->orderBy('pla.titulo')
            ->orderBy('p.NumPatrimonio')
            ->get([
                'pla.titulo as conta_titulo', 'pla.codigo as conta_codigo', 'p.NumPatrimonio', 'p.Descricao',
                'p.DatadeIncorporacao', 'p.ValordaReavaliacao', 'p.ValorAquisicao', 'se.setor',
            ])
            ->map(function (object $bem): array {
                $ajustado = (float) ($bem->ValordaReavaliacao ?? 0);

                return [
                    'conta_titulo' => $bem->conta_titulo,
                    'conta_codigo' => $bem->conta_codigo,
                    'num_patrimonio' => $bem->NumPatrimonio,
                    'descricao' => $bem->Descricao,
                    'data_aquisicao' => $bem->DatadeIncorporacao ? substr($bem->DatadeIncorporacao, 8, 2).'/'.substr($bem->DatadeIncorporacao, 5, 2).'/'.substr($bem->DatadeIncorporacao, 0, 4) : null,
                    'valor_aquisicao' => (float) ($bem->ValorAquisicao ?? 0),
                    'valor_ajustado' => $ajustado,
                    'valor_atual' => $ajustado > 0 ? $ajustado : (float) $bem->ValorAquisicao,
                    'setor' => $bem->setor,
                ];
            });
    }
}
