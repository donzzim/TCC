<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use Illuminate\Support\Collection;

/**
 * Contrato comum dos Services de Relatorios Gerais.
 *
 * Cada Service replica UMA query do legado
 * (docs/relatorios-gerais/analise-queries-legado.md) e devolve apenas o
 * dataset (linhas). Subtotais / "quadros resumo" / ROLLUP ficam a cargo de
 * quem apresenta, exceto quando o calculo do total e nao-trivial — nesses
 * casos o Service expoe um metodo `totais()` adicional.
 *
 * @template TLinha of array<string, mixed>
 */
interface RelatorioService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection;
}
