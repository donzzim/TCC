<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use Illuminate\Support\Collection;

/**
 * Relatorio "Analitico Contabil" (Bens Moveis).
 *
 * Legado: relatorios-prints-queries/bens-moveis/analitico-contabil.php
 *
 *   SELECT c.codigo, p.NumPatrimonio, p.DatadeIncorporacao, p.ValorAquisicao,
 *          (p.VidaUtilSIAFi - p.UtilizacaodoBemMeses) VidaUtilRemanescente,
 *          p.DataDisponibilizacao, p.Valor ValorLiquidoContabil,
 *          p.ValorResidual, p.ValordaReavaliacao ValorReavaliado
 *   FROM mat_patrimonio p LEFT JOIN mat_planocontas c ON p.ContaContabil = c.id
 *   WHERE p.DatadeIncorporacao BETWEEN :inicio AND :termino
 *     {AND p.ContaContabil = :conta} {AND p.situacao_contabil = :situacao}
 *   ORDER BY p.ContaContabil, p.NumPatrimonio
 */
final class AnaliticoContabilService implements RelatorioService
{
    /**
     * @return Collection<int, array{
     *     conta_codigo: ?string, num_patrimonio: mixed, data_aquisicao: ?string,
     *     valor_aquisicao: float, vida_util_remanescente: int|float|null,
     *     data_disponibilizacao: ?string, valor_liquido_contabil: float,
     *     valor_residual: float, valor_reavaliado: float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        // BUG-LEGADO: BETWEEN com valores crus ("Y-m-d 00:00:00"); termino nao
        // e estendido ate o fim do dia. Mantido.
        $inicio = $filtros->inicioObrigatorio()->format('Y-m-d 00:00:00');
        $termino = $filtros->terminoObrigatorio()->format('Y-m-d 00:00:00');

        return BemMovel::query()
            ->with('contaContabilRef:id,codigo')
            ->whereBetween('DatadeIncorporacao', [$inicio, $termino])
            ->when($filtros->conta, fn ($q) => $q->where('ContaContabil', $filtros->conta))
            ->when($filtros->situacaoContabil, fn ($q) => $q->where('situacao_contabil', $filtros->situacaoContabil))
            ->orderBy('ContaContabil')
            ->orderBy('NumPatrimonio')
            ->get([
                'id', 'ContaContabil', 'NumPatrimonio', 'DatadeIncorporacao', 'ValorAquisicao',
                'VidaUtilSIAFi', 'UtilizacaodoBemMeses', 'DataDisponibilizacao', 'Valor',
                'ValorResidual', 'ValordaReavaliacao',
            ])
            ->map(fn (BemMovel $bem): array => [
                'conta_codigo' => $bem->contaContabilRef?->codigo,
                'num_patrimonio' => $bem->NumPatrimonio,
                'data_aquisicao' => $bem->DatadeIncorporacao?->format('d/m/Y'),
                'valor_aquisicao' => (float) $bem->ValorAquisicao,
                // Legado: (p.VidaUtilSIAFi - p.UtilizacaodoBemMeses). Colunas podem vir como texto.
                'vida_util_remanescente' => (int) ($bem->VidaUtilSIAFi ?? 0) - (int) ($bem->UtilizacaodoBemMeses ?? 0),
                'data_disponibilizacao' => $this->formatarData($bem->DataDisponibilizacao),
                'valor_liquido_contabil' => (float) $bem->Valor,
                'valor_residual' => (float) $bem->ValorResidual,
                'valor_reavaliado' => (float) $bem->ValordaReavaliacao,
            ]);
    }

    private function formatarData(mixed $valor): ?string
    {
        if (blank($valor) || $valor === '0000-00-00' || $valor === '0000-00-00 00:00:00') {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y');
    }
}
