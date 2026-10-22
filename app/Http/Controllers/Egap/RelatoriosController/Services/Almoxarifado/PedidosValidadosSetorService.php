<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Almoxarifado\ItemPedido;
use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use App\Models\egap\Almoxarifado\Pedidos;
use Illuminate\Support\Collection;

/**
 * Relatorio "Pedidos Validados pelo Setor".
 *
 * Legado: relatorios-prints-queries/almoxarifado/pedidos-validados-por-setor.php
 *
 * Query 1 (pedidos): ped_pedidos LEFT jos_users LEFT mat_setores
 *   WHERE ped.setor_responsavel = 799 AND ped.idSituacao = 7 AND ped.date_time BETWEEN :inicio AND :termino
 *   ORDER BY ped.id
 * Query 2 (itens, no legado em loop por pedido): ped_itempedido LEFT dd/dr/el/un
 *   LEFT alm_estoque est ON iped.idPedido = est.id_pedido AND dd.id = est.material
 *
 * Aqui as duas consultas de itens/estoque sao feitas em lote (whereIn) para
 * evitar N+1; a juncao item x estoque (por pedido + material) e feita em PHP.
 */
final class PedidosValidadosSetorService implements RelatorioService
{
    /**
     * Setor responsavel pela validacao dos pedidos (Seção de Material de Consumo).
     *
     * O legado fixava 799, mas nos dados atuais NENHUM pedido validado
     * (idSituacao = 7) tem setor_responsavel = 799 — todos estao em 1239.
     * Ajustado para 1239 (799 devolvia sempre lista vazia).
     */
    private const SETOR_RESPONSAVEL = 1239;

    /** Situacao "validado" (fixa no legado). */
    private const SITUACAO_VALIDADO = 7;

    /**
     * @return Collection<int, array{
     *     id: int, numero: string, ano: int, data: ?string, solicitante: ?string, setor: ?string,
     *     itens: list<array{
     *         descricao_resumida: ?string, descricao_detalhada: ?string, sigla: ?string, unidade: ?string,
     *         elemento_codigo: ?string, elemento_descricao: ?string,
     *         quantidade_solicitada: int, quantidade_atendida: int,
     *         valor_medio: float, quantidade_estoque: float, valor_total: float
     *     }>
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicio = $filtros->inicioHora();
        $termino = $filtros->fimHora();

        $pedidos = Pedidos::query()
            ->where('setor_responsavel', self::SETOR_RESPONSAVEL)
            ->where('idSituacao', self::SITUACAO_VALIDADO)
            ->whereBetween('date_time', [$inicio, $termino])
            ->with(['solicitante_get:id,name', 'setor_get:id,setor'])
            ->orderBy('id')
            ->get();

        if ($pedidos->isEmpty()) {
            return collect();
        }

        $pedidoIds = $pedidos->pluck('id')->all();

        $itensPorPedido = ItemPedido::query()
            ->whereIn('idPedido', $pedidoIds)
            ->with([
                'descricaoDetalhadaRef:id,descricao_detalhada,descricao_resumida,unidade_medida',
                'descricaoDetalhadaRef.descricao_resumida_text:id,Descricao,id_produto',
                'descricaoDetalhadaRef.descricao_resumida_text.produto_id:id,CodigodaClasse,DescricaodaClasse',
                'descricaoDetalhadaRef.unidadeMedida:id,Sigla,Unidade',
            ])
            ->get()
            ->groupBy('idPedido');

        // alm_estoque por (id_pedido, material) — corresponde ao LEFT JOIN do legado.
        // toBase(): o cast decimal:2 do model cortaria o preco medio antes do ROUND(..., 4) do legado.
        $estoquePorChave = MovimentacaoEstoque::query()
            ->toBase()
            ->whereIn('id_pedido', $pedidoIds)
            ->get(['id_pedido', 'material', 'preco_medio_estoque', 'quantidade_estoque'])
            ->mapWithKeys(fn ($e) => [$e->id_pedido.'|'.$e->material => $e]);

        return $pedidos->map(function (Pedidos $pedido) use ($itensPorPedido, $estoquePorChave): array {
            $itens = ($itensPorPedido[$pedido->id] ?? collect())->map(function (ItemPedido $item) use ($pedido, $estoquePorChave): array {
                $dd = $item->descricaoDetalhadaRef;
                $estoque = $estoquePorChave[$pedido->id.'|'.($dd?->id)] ?? null;

                $valorMedio = round((float) ($estoque->preco_medio_estoque ?? 0), 4);
                $atendida = (int) ($item->QuantidadeMaterialAtendida ?? 0);

                return [
                    'descricao_resumida' => $dd?->descricao_resumida_text?->Descricao,
                    'descricao_detalhada' => $dd?->descricao_detalhada,
                    'sigla' => $dd?->unidadeMedida?->Sigla,
                    'unidade' => $dd?->unidadeMedida?->Unidade,
                    'elemento_codigo' => $dd?->descricao_resumida_text?->produto_id?->CodigodaClasse,
                    'elemento_descricao' => $dd?->descricao_resumida_text?->produto_id?->DescricaodaClasse,
                    'quantidade_solicitada' => (int) ($item->QuantidadeMaterial ?? 0),
                    'quantidade_atendida' => $atendida,
                    'valor_medio' => $valorMedio,
                    'quantidade_estoque' => round((float) ($estoque->quantidade_estoque ?? 0), 4),
                    // Legado: valor_total_estoque = QuantidadeMaterialAtendida * valor_medio
                    'valor_total' => $atendida * $valorMedio,
                ];
            })->all();

            return [
                'id' => (int) $pedido->id,
                'numero' => str_pad((string) $pedido->id, 8, '0', STR_PAD_LEFT),
                'ano' => (int) $pedido->date_time?->year,
                'data' => $pedido->date_time?->format('d/m/Y'),
                'solicitante' => $pedido->solicitante_get?->name,
                'setor' => $pedido->setor_get?->setor,
                'itens' => $itens,
            ];
        })->values();
    }
}
