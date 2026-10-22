<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Relatorio "Media de Consumo por Material e Setor (por Comarca / Unidade Organizacional)".
 *
 * Legado: relatorios-prints-queries/almoxarifado/media-de-consumo-por-material-comarca.php
 *
 * Igual ao "Media de Consumo por Material", porem quebrado por Unidade
 * Organizacional (mat_setores.CodigodaUO -> mat_setores). O "estoque atual" e o
 * consumo sao apurados por (material, id_setor) e depois somados por
 * (material, UnidadeOrganizacional).
 *
 * Aqui usamos o Eloquent builder com joins (mat_setores duas vezes) para deixar
 * a agregacao no banco; o unico selectRaw e o do SUM/MAX por grupo.
 *
 * Obs.: diferente do relatorio "por material", aqui o `consumo_medio` do legado
 * JA vem com os parenteses corretos: SUM(consumo) / (TRUNCATE(dias/30,0) + 1).
 */
final class MediaConsumoMaterialComarcaService implements RelatorioService
{
    /**
     * @return Collection<int, array{
     *     id_descricao: int, descricao_detalhada: ?string, unidade_organizacional: ?string,
     *     quantidade_atual: float, quantidade_consumida: float,
     *     dias: int, meses: int, consumo_medio: ?float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicio = $filtros->inicioHora();
        $termino = $filtros->fimHora();
        $dias = $this->diasEntre($inicio, $termino);

        // Ultima movimentacao de saida por (material, id_setor).
        $idsUltimaSaida = MovimentacaoEstoque::query()
            ->where('tipo_movimentacao', 2)
            ->when($filtros->temMateriais(), fn ($q) => $q->whereIn('material', $filtros->materiais))
            ->groupBy('material', 'id_setor')
            ->selectRaw('MAX(id) as id'); // agregado por grupo

        $atual = MovimentacaoEstoque::query()
            ->from('egap_alm_estoque as est')
            ->join('setores as sec', 'sec.id', '=', 'est.id_setor')
            ->join('setores as unid', 'sec.codigodauo', '=', 'unid.id')
            ->join('egap_mat_descricaodetalhada as dd', 'est.material', '=', 'dd.id')
            ->whereIn('est.id', $idsUltimaSaida)
            ->when(
                $filtros->temMateriais(),
                fn ($q) => $q->whereIn('dd.id', $filtros->materiais),
                fn ($q) => $q->where('dd.item_estoque', '1'),
            )
            ->groupBy('dd.id', 'dd.descricao_detalhada', 'unid.unidadeorganizacional')
            ->orderBy('dd.descricao_detalhada')
            ->orderBy('unid.unidadeorganizacional')
            ->select([
                'dd.id as id_descricao',
                'dd.descricao_detalhada',
                'unid.unidadeorganizacional as unidade_organizacional',
            ])
            ->selectRaw('SUM(est.quantidade_estoque) as quantidade_atual')
            ->get();

        $consumoPorChave = MovimentacaoEstoque::query()
            ->from('egap_alm_estoque as e')
            ->join('setores as sec', 'sec.id', '=', 'e.id_setor')
            ->join('setores as unid', 'sec.codigodauo', '=', 'unid.id')
            ->where('e.tipo_movimentacao', 2)
            ->whereBetween('e.date_time', [$inicio, $termino])
            ->when($filtros->temMateriais(), fn ($q) => $q->whereIn('e.material', $filtros->materiais))
            ->groupBy('e.material', 'unid.unidadeorganizacional')
            ->select(['e.material', 'unid.unidadeorganizacional as unidade_organizacional'])
            ->selectRaw('SUM(e.quantidade) as consumida')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->material.'|'.$r->unidade_organizacional => (float) $r->consumida]);

        return $atual->map(function ($r) use ($consumoPorChave, $dias): array {
            $consumo = $consumoPorChave[$r->id_descricao.'|'.$r->unidade_organizacional] ?? 0.0;
            [$meses, $consumoMedio] = $this->mesesEConsumoMedio($dias, $consumo);

            return [
                'id_descricao' => (int) $r->id_descricao,
                'descricao_detalhada' => $r->descricao_detalhada,
                'unidade_organizacional' => $r->unidade_organizacional,
                'quantidade_atual' => (float) ($r->quantidade_atual ?? 0),
                'quantidade_consumida' => $consumo,
                'dias' => $dias,
                'meses' => $meses,
                'consumo_medio' => $consumoMedio,
            ];
        })->values();
    }

    private function diasEntre(string $inicio, string $termino): int
    {
        return (int) Carbon::parse($inicio)->diffInDays(Carbon::parse($termino));
    }

    /**
     * @return array{0: int, 1: ?float}
     */
    private function mesesEConsumoMedio(int $dias, float $consumo): array
    {
        $trunc = intdiv(max($dias, 0), 30);

        if ($dias % 30 > 20) {
            return [$trunc + 1, ($consumo / ($trunc + 1))];
        }

        // Legado: SUM(consumo) / NULLIF(TRUNCATE(dias/30,0), 0)
        return [$trunc, $trunc === 0 ? null : $consumo / $trunc];
    }
}
