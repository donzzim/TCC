<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensIntangiveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensIntangiveis\BemIntangivel;
use Illuminate\Support\Collection;

/**
 * Relatorio "Inventario dos Bens Intangiveis".
 *
 * Legado: relatorios-prints-queries/bens-intangiveis/inventario-dos-bens-intangiveis.php
 *
 *   SELECT pla.titulo, pla.codigo, p.inscricao_generica, p.nome,
 *          p.data_aquisicao, p.valor_aquisicao, p.amortizacao_acumulada,
 *          p.valor_liquido_contabil, p.quantidade, p.vida_util_remanescente
 *   FROM int_intangivel p INNER JOIN mat_planocontas pla ON p.id_planocontas = pla.id
 *   ORDER BY pla.titulo, p.inscricao_generica
 *
 * Sem filtros (o `inicio` do legado so serve para o titulo/ano). Agrupamento
 * por conta contabil e total ficam na apresentacao.
 */
final class InventarioBensIntangiveisService implements RelatorioService
{
    /**
     * @return Collection<int, array{
     *     conta_codigo: ?string, conta_titulo: ?string, inscricao_generica: ?string, nome: ?string,
     *     data_aquisicao: ?string, valor_aquisicao: float, amortizacao_acumulada: float,
     *     valor_liquido_contabil: float, quantidade: int, vida_util_remanescente: mixed
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        return BemIntangivel::query()
            ->with('idPlanoContasRef:id,codigo,titulo')
            ->get()
            ->map(fn (BemIntangivel $bem): array => [
                'conta_codigo' => $bem->idPlanoContasRef?->codigo,
                'conta_titulo' => $bem->idPlanoContasRef?->titulo,
                'inscricao_generica' => $bem->inscricao_generica,
                'nome' => $bem->nome,
                'data_aquisicao' => $bem->data_aquisicao?->format('d/m/Y'),
                'valor_aquisicao' => (float) $bem->valor_aquisicao,
                'amortizacao_acumulada' => (float) $bem->amortizacao_acumulada,
                'valor_liquido_contabil' => (float) $bem->valor_liquido_contabil,
                'quantidade' => (int) $bem->quantidade,
                'vida_util_remanescente' => $bem->vida_util_remanescente,
            ])
            // Legado: ORDER BY pla.titulo, p.inscricao_generica
            ->sort(fn ($a, $b) => [(string) $a['conta_titulo'], (string) $a['inscricao_generica']]
                <=> [(string) $b['conta_titulo'], (string) $b['inscricao_generica']])
            ->values();
    }
}
