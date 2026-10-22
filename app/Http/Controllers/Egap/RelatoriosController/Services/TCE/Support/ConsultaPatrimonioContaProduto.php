<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Support;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use Closure;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Support\Collection;

/**
 * Helpers compartilhados das tabelas TCE de bens moveis (10 e 11): consultas
 * sobre mat_patrimonio agregadas por (conta contabil x produto), com o filtro
 * `$filtro` (conta / situacao_contabil) do legado. O legado tambem tinha
 * `unidade_gestora`, mas o campo era sempre oculto no JS e nunca chegava a estes
 * relatorios — omitido.
 */
trait ConsultaPatrimonioContaProduto
{
    private const ZERO_DATE = '0000-00-00 00:00:00';

    /**
     * Base: combos conta contabil x produto de mat_patrimonio.
     *
     * @return Collection<int, object>
     */
    protected function tuplasPatrimonioContaProduto(FiltrosRelatorio $filtros): Collection
    {
        $query = BemMovel::query()
            ->from('egap_mat_patrimonio as p')
            ->join('egap_mat_planocontas as pc', 'p.ContaContabil', '=', 'pc.id')
            ->join('egap_mat_produtos as pr', 'p.Produto', '=', 'pr.id')
            ->groupBy('pc.id', 'pr.id', 'pc.codigo', 'pr.CodigodaClasse', 'pr.DescricaodaClasse')
            ->orderBy('pc.codigo')
            ->orderBy('pr.CodigodaClasse')
            ->select([
                'pc.id as id_conta',
                'pr.id as id_produto',
                'pc.codigo as conta_contabil',
                'pr.CodigodaClasse as produto',
                'pr.DescricaodaClasse as descricao',
            ]);

        $this->aplicarFiltroPatrimonio($query, $filtros, 'p.');

        return $query->get();
    }

    /**
     * Agregacao por (ContaContabil, Produto) sobre mat_patrimonio.
     *
     * @param  string  $expressaoSoma  ex.: "SUM(CASE WHEN ... END)" ou "SUM(ValordaReavaliacao)"
     * @param  Closure(BuilderContract): void  $where  restricoes especificas da subquery
     * @return Collection<string, object>  keyBy "id_conta|id_produto"
     */
    protected function somaPatrimonioPorContaProduto(FiltrosRelatorio $filtros, string $expressaoSoma, Closure $where): Collection
    {
        $query = BemMovel::query()
            ->groupBy('ContaContabil', 'Produto')
            ->select(['ContaContabil as id_conta', 'Produto as id_produto'])
            // Agregado com CASE/SUM: sem equivalente fluente para GROUP BY.
            ->selectRaw($expressaoSoma.' as valor');

        $where($query);
        $this->aplicarFiltroPatrimonio($query, $filtros);

        return $query->get()->keyBy(fn ($r) => $r->id_conta.'|'.$r->id_produto);
    }

    /**
     * `$filtro` do legado: conta / situacao_contabil.
     */
    protected function aplicarFiltroPatrimonio(BuilderContract $query, FiltrosRelatorio $filtros, string $prefixo = ''): void
    {
        $query
            ->when($filtros->conta, fn ($q) => $q->where($prefixo.'ContaContabil', $filtros->conta))
            ->when($filtros->situacaoContabil, fn ($q) => $q->where($prefixo.'situacao_contabil', $filtros->situacaoContabil));
    }

    /**
     * Legado: (DatadaReavaliacao < :limite OR DatadaReavaliacao = '0000-00-00 00:00:00')
     */
    protected function reavaliacaoAntesDe(BuilderContract $query, string $limite, string $prefixo = ''): void
    {
        $query->where(fn ($q) => $q
            ->where($prefixo.'DatadaReavaliacao', '<', $limite)
            ->orWhere($prefixo.'DatadaReavaliacao', self::ZERO_DATE));
    }

    /**
     * "SUM(CASE WHEN DatadeIncorporacao < ':corte' THEN ValordaReavaliacao ELSE ValorAquisicao END)"
     */
    protected function somaValorHistorico(string $corte): string
    {
        return "SUM(CASE WHEN DatadeIncorporacao < '{$corte}' THEN ValordaReavaliacao ELSE ValorAquisicao END)";
    }
}
