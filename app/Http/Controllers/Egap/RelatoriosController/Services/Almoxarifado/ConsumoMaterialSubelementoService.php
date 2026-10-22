<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use App\Models\egap\Cadastro\DescricaoDetalhada;
use Illuminate\Support\Collection;

/**
 * Relatorio "Consumo de Material por Subelemento de Despesa".
 *
 * Legado: relatorios-prints-queries/almoxarifado/consumo-de-material-por-subelemento-despesa.php
 *
 *   SELECT el.CodigodaClasse, dd.id, dd.descricao_detalhada,
 *          est_consumo.quantidade_consumida,
 *          ultimo_preco_atual.preco_unitario preco_atual,
 *          (consumo * preco_atual) sub_total
 *   FROM alm_estoque est
 *     INNER JOIN dd / dr / mat_produtos el
 *     INNER JOIN (MAX(id) por material WHERE tipo=2) est_atual
 *     INNER JOIN (SUM(quantidade) por material WHERE tipo=2 AND periodo) est_consumo
 *     LEFT  JOIN (MAX(id) por material, SEM filtro) preco_atual
 *     LEFT  JOIN (id, IFNULL(preco_unitario,0)) ultimo_preco_atual
 *   WHERE {dd.item_estoque = 1 | dd.id IN (:materiais)}
 *   ORDER BY el.CodigodaClasse, dd.descricao_detalhada
 *
 * `preco_atual` = preco_unitario da ULTIMA movimentacao do material (qualquer tipo).
 * `sub_total` = consumo * preco_atual (em PHP).
 * Agrupamento por subelemento (CodigodaClasse) e feito na apresentacao.
 */
final class ConsumoMaterialSubelementoService implements RelatorioService
{
    /**
     * @return Collection<int, array{
     *     codigo_classe: ?string, id_descricao: int, descricao_detalhada: ?string,
     *     quantidade_consumida: float, preco_atual: float, sub_total: float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicio = $filtros->inicioHora();
        $termino = $filtros->fimHora();

        // Consumo (saidas) no periodo por material — INNER no legado: so materiais com consumo.
        $consumoPorMaterial = MovimentacaoEstoque::query()
            ->where('tipo_movimentacao', 2)
            ->whereBetween('date_time', [$inicio, $termino])
            ->groupBy('material')
            ->selectRaw('material, SUM(quantidade) as quantidade_consumida')
            ->pluck('quantidade_consumida', 'material')
            ->map(fn ($v) => (float) $v);

        if ($consumoPorMaterial->isEmpty()) {
            return collect();
        }

        $materiais = DescricaoDetalhada::query()
            ->whereIn('id', $consumoPorMaterial->keys())
            ->when(
                $filtros->temMateriais(),
                fn ($q) => $q->whereKey($filtros->materiais),
                fn ($q) => $q->where('item_estoque', '1'),
            )
            ->with('descricao_resumida_text.produto_id:id,CodigodaClasse')
            ->get(['id', 'descricao_detalhada', 'descricao_resumida']);

        if ($materiais->isEmpty()) {
            return collect();
        }

        $precoPorMaterial = $this->precoAtualPorMaterial($materiais->pluck('id')->all());

        return $materiais
            ->map(function ($material) use ($consumoPorMaterial, $precoPorMaterial): array {
                $consumo = (float) ($consumoPorMaterial[$material->id] ?? 0);
                $preco = (float) ($precoPorMaterial[$material->id] ?? 0);

                return [
                    'codigo_classe' => $material->descricao_resumida_text?->produto_id?->CodigodaClasse,
                    'id_descricao' => (int) $material->id,
                    'descricao_detalhada' => $material->descricao_detalhada,
                    'quantidade_consumida' => $consumo,
                    'preco_atual' => $preco,
                    'sub_total' => $consumo * $preco,
                ];
            })
            ->sortBy([['codigo_classe', 'asc'], ['descricao_detalhada', 'asc']])
            ->values();
    }

    /**
     * preco_unitario da ultima movimentacao (qualquer tipo) de cada material.
     *
     * @param  list<int>  $ids
     * @return Collection<int, float>  keyBy material
     */
    private function precoAtualPorMaterial(array $ids): Collection
    {
        $idsUltima = MovimentacaoEstoque::query()
            ->whereIn('material', $ids)
            ->groupBy('material')
            ->selectRaw('MAX(id) as id');

        // toBase(): o cast decimal:2 do model arredondaria o preco (3,9376 -> 3,94)
        // antes da multiplicacao; o legado usa o valor cheio.
        return MovimentacaoEstoque::query()
            ->toBase()
            ->joinSub($idsUltima, 'ult', 'ult.id', '=', 'egap_alm_estoque.id')
            ->pluck('preco_unitario', 'material')
            ->map(fn ($v) => (float) ($v ?? 0));
    }
}
