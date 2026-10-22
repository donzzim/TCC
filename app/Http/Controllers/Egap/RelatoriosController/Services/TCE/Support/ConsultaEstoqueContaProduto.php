<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Support;

use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use Illuminate\Support\Collection;

/**
 * Helpers compartilhados das tabelas TCE de almoxarifado (14 a 17): montam
 * consultas sobre alm_estoque agregadas por (conta contabil x produto).
 */
trait ConsultaEstoqueContaProduto
{
    /**
     * Combos conta contabil x produto que tem estoque, filtrados por tipo de
     * material (`<>` P = consumo, `=` P = permanente).
     *
     * @return Collection<int, object>
     */
    protected function tuplasContaProduto(string $operadorTipoMaterial): Collection
    {
        return MovimentacaoEstoque::query()
            ->from('egap_alm_estoque as est')
            ->join('egap_mat_descricaodetalhada as dd', 'est.material', '=', 'dd.id')
            ->join('egap_mat_descricaoresumida as dr', 'dd.descricao_resumida', '=', 'dr.id')
            ->join('egap_mat_planocontas as pc', 'dr.ContaContabil', '=', 'pc.id')
            ->join('egap_mat_produtos as pr', 'dr.id_produto', '=', 'pr.id')
            ->where('dr.id_tipo_material', $operadorTipoMaterial, 'P')
            ->groupBy('pc.id', 'pr.id', 'pc.codigo', 'pr.CodigodaClasse', 'pr.DescricaodaClasse')
            ->orderBy('pc.codigo')
            ->orderBy('pr.CodigodaClasse')
            ->get([
                'pc.id as id_conta',
                'pr.id as id_produto',
                'pc.codigo as conta_contabil',
                'pr.CodigodaClasse as produto',
                'pr.DescricaodaClasse as descricao',
            ]);
    }

    /**
     * SUM(est.valor_total) por (conta contabil x produto), para uma movimentacao
     * de estoque no periodo. Opcionalmente restrito por tipo de documento da NF
     * (join com alm_notafiscal).
     *
     * @return Collection<string, object>  keyBy "id_conta|id_produto"
     */
    protected function somaEstoquePorContaProduto(
        int $tipoMovimentacao,
        string $inicio,
        string $termino,
        ?int $tipoDocumentoNf = null,
    ): Collection {
        $query = MovimentacaoEstoque::query()
            ->from('egap_alm_estoque as est')
            ->join('egap_mat_descricaodetalhada as dd', 'est.material', '=', 'dd.id')
            ->join('egap_mat_descricaoresumida as dr', 'dd.descricao_resumida', '=', 'dr.id')
            ->where('est.tipo_movimentacao', $tipoMovimentacao)
            ->whereBetween('est.date_time', [$inicio, $termino]);

        if ($tipoDocumentoNf !== null) {
            $query->join('egap_alm_notafiscal as nf', 'est.nota_fiscal', '=', 'nf.id')
                ->where('nf.tipo_documento', $tipoDocumentoNf);
        }

        return $query
            ->groupBy('dr.ContaContabil', 'dr.id_produto')
            ->select(['dr.ContaContabil as id_conta', 'dr.id_produto as id_produto'])
            ->selectRaw('SUM(est.valor_total) as valor')
            ->get()
            ->keyBy(fn ($r) => $r->id_conta.'|'.$r->id_produto);
    }
}
