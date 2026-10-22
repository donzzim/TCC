<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Depreciacao;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Relatorio "Inventario dos Bens Moveis".
 *
 * Legado: relatorios-prints-queries/bens-moveis/inventario-bens-moveis.php
 *
 *   SELECT pla.titulo, pla.codigo, p.NumPatrimonio, p.Descricao, p.DatadeIncorporacao,
 *          IF(IFNULL(p.ValordaReavaliacao,0) > 0, p.ValordaReavaliacao, p.ValorAquisicao) ValorReavaliado,
 *          IFNULL(dep.depreciacao_acumulada,0) DepreciacaoAcumulada,
 *          IFNULL(dep.valor_liquido_contabil,0) Valor, se.Setor
 *   FROM mat_patrimonio p
 *     INNER JOIN mat_planocontas pla / mat_setores se
 *     LEFT JOIN (mat_depreciacao no mes do termino) dep ON p.id = dep.patrimonio
 *   WHERE p.SituacaoBem IN (1,7)
 *     AND p.DatadeIncorporacao <= :termino
 *     AND YEAR(p.DatadaReavaliacao) <= YEAR(:termino)
 *     AND p.ContaContabil = :conta            -- OBRIGATORIO no legado
 *     {AND p.situacao_contabil = :situacao}
 *   ORDER BY pla.titulo, p.NumPatrimonio
 */
final class InventarioBensMoveisService implements RelatorioService
{
    /**
     * @return Collection<int, array{
     *     conta_titulo: ?string, conta_codigo: ?string, num_patrimonio: mixed, descricao: ?string,
     *     data_aquisicao: ?string, valor_reavaliado: float, depreciacao_acumulada: float,
     *     valor: float, setor: ?string
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        if ($filtros->conta === null) {
            throw new \InvalidArgumentException('Filtro "conta" obrigatorio para o Inventario dos Bens Moveis.');
        }

        $terminoData = $filtros->fimData();          // legado: str_replace('00:00:00','') => data
        $anoTermino = $filtros->anoDoTermino();

        $bens = BemMovel::query()
            ->with(['contaContabilRef:id,codigo,titulo', 'setorRef:id,setor'])
            ->whereIn('SituacaoBem', [1, 7])
            ->whereDate('DatadeIncorporacao', '<=', $terminoData)
            ->whereYear('DatadaReavaliacao', '<=', $anoTermino)
            ->where('ContaContabil', $filtros->conta)
            ->when($filtros->situacaoContabil, fn ($q) => $q->where('situacao_contabil', $filtros->situacaoContabil))
            ->get(['id', 'ContaContabil', 'NumPatrimonio', 'Descricao', 'Setor', 'DatadeIncorporacao', 'ValordaReavaliacao', 'ValorAquisicao']);

        if ($bens->isEmpty()) {
            return collect();
        }

        $depreciacaoPorBem = $this->depreciacaoDoMes($bens->pluck('id')->all(), $anoTermino, (int) $filtros->terminoObrigatorio()->month);

        return $bens
            ->map(function (BemMovel $bem) use ($depreciacaoPorBem): array {
                $dep = $depreciacaoPorBem[$bem->id] ?? null;
                $reavaliacao = (float) ($bem->ValordaReavaliacao ?? 0);

                return [
                    'conta_titulo' => $bem->contaContabilRef?->titulo,
                    'conta_codigo' => $bem->contaContabilRef?->codigo,
                    'num_patrimonio' => $bem->NumPatrimonio,
                    'descricao' => $bem->Descricao,
                    'data_aquisicao' => $bem->DatadeIncorporacao?->format('d/m/Y'),
                    'valor_reavaliado' => $reavaliacao > 0 ? $reavaliacao : (float) $bem->ValorAquisicao,
                    'depreciacao_acumulada' => (float) ($dep->depreciacao_acumulada ?? 0),
                    'valor' => (float) ($dep->valor_liquido_contabil ?? 0),
                    'setor' => $bem->setorRef?->setor,
                ];
            })
            // Legado: ORDER BY pla.titulo, p.NumPatrimonio
            ->sort(fn ($a, $b) => [(string) $a['conta_titulo'], (string) $a['num_patrimonio']]
                <=> [(string) $b['conta_titulo'], (string) $b['num_patrimonio']])
            ->values();
    }

    /**
     * mat_depreciacao no mes/ano de referencia, por patrimonio.
     *
     * Legado nao agrupa; se houver mais de uma linha no mes, mantemos a ultima.
     * A lista de patrimonios pode ter dezenas de milhares de itens (relatorio de
     * um plano de contas inteiro) — por isso `whereIn` em lotes (limite de
     * placeholders do MySQL) e BETWEEN sargable no lugar de whereYear/whereMonth.
     *
     * @param  list<int>  $bemIds
     * @return Collection<int, Depreciacao>  keyBy patrimonio
     */
    private function depreciacaoDoMes(array $bemIds, int $ano, int $mes): Collection
    {
        $mesRef = Carbon::create($ano, $mes, 1);
        $intervalo = [
            $mesRef->copy()->startOfMonth()->format('Y-m-d 00:00:00'),
            $mesRef->copy()->endOfMonth()->format('Y-m-d 23:59:59'),
        ];

        $resultado = collect();

        foreach (array_chunk($bemIds, 20000) as $lote) {
            $resultado = $resultado->union(
                Depreciacao::query()
                    ->whereIn('patrimonio', $lote)
                    ->whereBetween('data_calculo', $intervalo)
                    ->get(['patrimonio', 'depreciacao_acumulada', 'valor_liquido_contabil'])
                    ->keyBy('patrimonio')
            );
        }

        return $resultado;
    }
}
