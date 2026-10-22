<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use App\Models\egap\Cadastro\DescricaoDetalhada;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Relatorio "Media de Consumo por Material".
 *
 * Legado: relatorios-prints-queries/almoxarifado/media-de-consumo-por-material.php
 *
 * Estrutura do legado (3 conjuntos): materiais alvo, "estoque atual" (ultima
 * saida por material) e consumo no periodo. Aqui isso vira 3 consultas Eloquent
 * simples + juncao em PHP — sem joinSub:
 *
 *   1. DescricaoDetalhada  -> materiais (filtro item_estoque=1 ou id IN :materiais)
 *   2. MovimentacaoEstoque -> ultima movimentacao de saida (tipo 2) por material
 *   3. MovimentacaoEstoque -> SUM(quantidade) de saidas no periodo, por material
 *
 * `dias`, `meses` e `consumo_medio` sao derivados em PHP.
 */
final class MediaConsumoMaterialService implements RelatorioService
{
    /**
     * @return Collection<int, array{
     *     id_descricao: int, descricao_detalhada: ?string,
     *     quantidade_atual: float, quantidade_consumida: float,
     *     dias: int, meses: int, consumo_medio: ?float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicio = $filtros->inicioHora();   // "Y-m-d 00:00:01"
        $termino = $filtros->fimHora();     // "Y-m-d 23:59:59"
        $dias = $this->diasEntre($inicio, $termino);

        $materiais = DescricaoDetalhada::query()
            ->when(
                $filtros->temMateriais(),
                fn ($q) => $q->whereKey($filtros->materiais),
                // Coluna varchar: comparar com string usa o indice (545 ms -> 11 ms).
                fn ($q) => $q->where('item_estoque', '1'),
            )
            ->orderBy('descricao_detalhada')
            ->get(['id', 'descricao_detalhada']);

        if ($materiais->isEmpty()) {
            return collect();
        }

        $ids = $materiais->pluck('id')->all();

        $estoqueAtualPorMaterial = $this->estoqueAtualPorMaterial($ids);
        $consumoPorMaterial = $this->consumoPorMaterial($ids, $inicio, $termino);

        return $materiais
            // Legado: INNER JOIN na ultima saida — material que nunca saiu nao aparece.
            ->filter(fn ($material) => $estoqueAtualPorMaterial->has($material->id))
            ->map(function ($material) use ($estoqueAtualPorMaterial, $consumoPorMaterial, $dias): array {
                $consumo = (float) ($consumoPorMaterial[$material->id] ?? 0);
                [$meses, $consumoMedio] = $this->mesesEConsumoMedio($dias, $consumo);

                return [
                    'id_descricao' => (int) $material->id,
                    'descricao_detalhada' => $material->descricao_detalhada,
                    'quantidade_atual' => (float) ($estoqueAtualPorMaterial[$material->id]->quantidade_estoque ?? 0),
                    'quantidade_consumida' => $consumo,
                    'dias' => $dias,
                    'meses' => $meses,
                    'consumo_medio' => $consumoMedio,
                ];
            })
            ->values();
    }

    /**
     * Ultima movimentacao de saida (tipo 2) por material.
     *
     * Nao usa MovimentacaoEstoque::estoquesAtuais() porque aquele helper pega a
     * ultima mov. de QUALQUER tipo; o legado restringe a tipo_movimentacao = 2.
     *
     * @param  list<int>  $ids
     * @return Collection<int, object>  keyBy material
     */
    private function estoqueAtualPorMaterial(array $ids): Collection
    {
        $idsUltimaSaida = MovimentacaoEstoque::query()
            ->where('tipo_movimentacao', 2)
            ->whereIn('material', $ids)
            ->groupBy('material')
            // MAX(id) por grupo: agregado sem equivalente fluente (mesmo padrao
            // de MovimentacaoEstoque::estoquesAtuais()).
            ->selectRaw('MAX(id) as id')
            ->pluck('id');

        // toBase(): o cast 'integer' do model truncaria estoques fracionados (5,76 -> 5).
        return MovimentacaoEstoque::query()
            ->toBase()
            ->whereIn('id', $idsUltimaSaida)
            ->get(['material', 'quantidade_estoque'])
            ->keyBy('material');
    }

    /**
     * SUM(quantidade) das saidas (tipo 2) no periodo, por material.
     *
     * @param  list<int>  $ids
     * @return Collection<int, float>  keyBy material
     */
    private function consumoPorMaterial(array $ids, string $inicio, string $termino): Collection
    {
        return MovimentacaoEstoque::query()
            ->where('tipo_movimentacao', 2)
            ->whereBetween('date_time', [$inicio, $termino])
            ->whereIn('material', $ids)
            ->groupBy('material')
            ->selectRaw('material, SUM(quantidade) as quantidade_consumida')
            ->pluck('quantidade_consumida', 'material')
            ->map(fn ($v) => (float) $v);
    }

    private function diasEntre(string $inicio, string $termino): int
    {
        // TIMESTAMPDIFF(day, inicio, termino): numero de dias completos.
        return (int) Carbon::parse($inicio)->diffInDays(Carbon::parse($termino));
    }

    /**
     * @return array{0: int, 1: ?float}  [meses, consumo_medio]
     */
    private function mesesEConsumoMedio(int $dias, float $consumo): array
    {
        $trunc = intdiv(max($dias, 0), 30); // TRUNCATE(dias/30, 0)

        if ($dias % 30 > 20) {
            $meses = $trunc + 1;
            // BUG-LEGADO: a precedencia do legado e "(@consumo / TRUNCATE(dias/30,0) + 1)",
            // ou seja, divide por $trunc e SO DEPOIS soma 1 (nao divide por $meses).
            // A versao "por comarca" corrige com parenteses; aqui mantemos fiel.
            $consumoMedio = $trunc === 0 ? null : ($consumo / $trunc) + 1;
        } else {
            $meses = $trunc;
            $consumoMedio = $trunc === 0 ? null : $consumo / $trunc;
        }

        return [$meses, $consumoMedio];
    }
}
