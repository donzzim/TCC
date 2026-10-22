<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use App\Models\egap\Cadastro\DescricaoDetalhada;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Collection;

/**
 * Relatorio "Balancete Contabil Analitico" (Almoxarifado).
 *
 * Legado: relatorios-prints-queries/almoxarifado/balancete-contabil-analitico.php
 *
 * O legado roda a mesma query 3x, uma por tipo de material ('C', 'D', 'P'),
 * e imprime 3 blocos. Colunas por material:
 *   - saldo anterior (qtd/valor) = ultima movimentacao ANTES da data inicio
 *   - entrada (qtd/valor) = SUM tipo=1 no periodo
 *   - saida  (qtd/valor) = SUM tipo=2 no periodo
 *   - qtd_atual  = qtd_ant + qtd_ent - qtd_sai
 *   - saldo_atual = valor_ant + valor_ent - valor_sai
 * Filtro final: (qtd_ant > 0 OR qtd_ent > 0 OR qtd_sai > 0).
 *
 * Traduzido para 4 consultas (anterior + entradas + saidas, todas agregadas por
 * material sobre alm_estoque; depois so os materiais COM atividade sao carregados
 * de mat_descricaodetalhada). Isso reproduz o `WHERE (ant>0 OR ent>0 OR sai>0)`
 * do legado e evita hidratar as ~32k descricoes detalhadas.
 */
final class BalanceteContabilAnaliticoService implements RelatorioService
{
    private const TIPOS = ['C', 'D', 'P'];

    private const ROTULO_TIPO = [
        'C' => 'Consumo',
        'D' => 'Consumo Durável',
        'P' => 'Permanente',
    ];

    /**
     * @return Collection<int, array{
     *     id: int, descricao_detalhada: ?string, tipo: string, elemento_codigo: ?string, elemento_descricao: ?string,
     *     qtd_anterior: float, valor_anterior: float, qtd_entrada: float, valor_entrada: float,
     *     qtd_saida: float, valor_saida: float, qtd_atual: float, saldo_atual: float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicioData = $filtros->inicioData();  // legado: DATE_FORMAT('$inicio','%Y-%m-%d')
        $inicio = $filtros->inicioHora();
        $termino = $filtros->fimHora();

        // Agregados primeiro (limitados a alm_estoque), sem lista previa de materiais.
        $anteriorPorMaterial = $this->saldoAnteriorPorMaterial($inicioData);
        $entradaPorMaterial = $this->somaMovimentacao(1, $inicio, $termino);
        $saidaPorMaterial = $this->somaMovimentacao(2, $inicio, $termino);

        // Materiais com atividade = uniao das chaves (legado: WHERE ant>0 OR ent>0 OR sai>0).
        $idsComAtividade = collect()
            ->concat($anteriorPorMaterial->keys())
            ->concat($entradaPorMaterial->keys())
            ->concat($saidaPorMaterial->keys())
            ->unique()
            ->values();

        if ($idsComAtividade->isEmpty()) {
            return collect();
        }

        $materiais = DescricaoDetalhada::query()
            ->whereKey($idsComAtividade)
            ->whereHas('descricao_resumida_text', fn ($q) => $q->whereIn('id_tipo_material', self::TIPOS))
            ->with([
                'descricao_resumida_text:id,id_tipo_material,id_produto',
                'descricao_resumida_text.produto_id:id,CodigodaClasse,DescricaodaClasse',
            ])
            ->get(['id', 'descricao_detalhada', 'descricao_resumida']);

        if ($materiais->isEmpty()) {
            return collect();
        }

        return $materiais
            ->map(function (DescricaoDetalhada $material) use ($anteriorPorMaterial, $entradaPorMaterial, $saidaPorMaterial): ?array {
                $tipo = $material->descricao_resumida_text?->id_tipo_material;
                if (! in_array($tipo, self::TIPOS, true)) {
                    return null;
                }

                $anterior = $anteriorPorMaterial[$material->id] ?? null;
                $entrada = $entradaPorMaterial[$material->id] ?? null;
                $saida = $saidaPorMaterial[$material->id] ?? null;

                $qtdAnterior = (float) ($anterior->quantidade_estoque ?? 0);
                $qtdEntrada = (float) ($entrada->quantidade ?? 0);
                $qtdSaida = (float) ($saida->quantidade ?? 0);

                // Filtro do legado: (anterior.quantidade_estoque > 0 OR entrada.quantidade > 0 OR saida.quantidade_estoque > 0)
                if ($qtdAnterior <= 0 && $qtdEntrada <= 0 && $qtdSaida <= 0) {
                    return null;
                }

                $valorAnterior = round((float) ($anterior->valor_total_estoque ?? 0), 4);
                $valorEntrada = round((float) ($entrada->total ?? 0), 4);
                // Legado: TRUNCATE(IFNULL(saida.total,0), 2)
                $valorSaida = $this->truncate((float) ($saida->total ?? 0), 2);

                return [
                    'id' => (int) $material->id,
                    'descricao_detalhada' => $material->descricao_detalhada,
                    'tipo' => self::ROTULO_TIPO[$tipo],
                    'elemento_codigo' => $material->descricao_resumida_text?->produto_id?->CodigodaClasse,
                    'elemento_descricao' => $material->descricao_resumida_text?->produto_id?->DescricaodaClasse,
                    'qtd_anterior' => round($qtdAnterior, 4),
                    'valor_anterior' => $valorAnterior,
                    'qtd_entrada' => round($qtdEntrada, 4),
                    'valor_entrada' => $valorEntrada,
                    'qtd_saida' => round($qtdSaida, 4),
                    'valor_saida' => $valorSaida,
                    'qtd_atual' => $qtdAnterior + $qtdEntrada - $qtdSaida,
                    'saldo_atual' => $valorAnterior + $valorEntrada - $valorSaida,
                ];
            })
            ->filter()
            // Legado: ORDER BY dr.id_tipo_material, dd.descricao_detalhada (C, D, P nessa ordem)
            ->sort(function ($a, $b): int {
                $ordem = array_flip(array_values(self::ROTULO_TIPO)); // 'Consumo' => 0, ...

                return [$ordem[$a['tipo']], (string) $a['descricao_detalhada']]
                    <=> [$ordem[$b['tipo']], (string) $b['descricao_detalhada']];
            })
            ->values();
    }

    /**
     * Ultima movimentacao de cada material ANTES da data de inicio (uma linha por
     * material). Query builder puro (toBase) para nao hidratar models.
     *
     * @return Collection<int, object>  keyBy material
     */
    private function saldoAnteriorPorMaterial(string $inicioData): Collection
    {
        $idsUltima = MovimentacaoEstoque::query()->toBase()
            ->where('date_time', '<', $inicioData)
            ->groupBy('material')
            ->selectRaw('MAX(id) as id');

        // joinSub em vez de whereIn(subquery): o MySQL materializa o MAX(id) uma vez.
        return MovimentacaoEstoque::query()->toBase()
            ->joinSub($idsUltima, 'ult', 'ult.id', '=', 'egap_alm_estoque.id')
            ->get(['material', 'quantidade_estoque', 'valor_total_estoque'])
            ->keyBy('material');
    }

    /**
     * SUM(quantidade) e SUM(valor_total) por material, para um tipo de
     * movimentacao no periodo.
     *
     * @return Collection<int, object>  keyBy material
     */
    private function somaMovimentacao(int $tipo, string $inicio, string $termino): Collection
    {
        return MovimentacaoEstoque::query()->toBase()
            ->where('tipo_movimentacao', $tipo)
            ->whereBetween('date_time', [$inicio, $termino])
            ->groupBy('material')
            ->selectRaw('material, SUM(quantidade) as quantidade, SUM(valor_total) as total')
            ->get()
            ->keyBy('material');
    }

    /**
     * TRUNCATE(valor, casas) do MySQL. Em float, (int) (77.6 * 100) da 7759
     * (77.6 * 100 = 7759.99...), entao trunca sobre a representacao decimal.
     */
    private function truncate(float $valor, int $casas): float
    {
        return BigDecimal::of((string) $valor)->toScale($casas, RoundingMode::Down)->toFloat();
    }
}
