<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Support;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Models\egap\Patrimonio\BensImoveis\BemImovel;
use App\Models\egap\Patrimonio\BensImoveis\Obra;
use App\Models\egap\Patrimonio\BensImoveis\Reavaliacao;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Helpers compartilhados das tabelas TCE de bens imoveis (12 e 13): carregam
 * imoveis + reavaliacoes + obras para apuracao por conta contabil em PHP
 * (evita a arvore de LEFT JOIN / subqueries do legado).
 */
trait ConsultaImovelPorConta
{
    private const ZERO_DATE = '0000-00-00 00:00:00';

    /**
     * Imoveis ativos (Id_situacao = 1), com a conta contabil carregada.
     * BUG-LEGADO: o legado filtra por `imo_imovel.id_planocontas` quando `conta`
     * e informada — aqui idem (coluna correta).
     *
     * @return Collection<int, BemImovel>
     */
    protected function imoveisAtivos(FiltrosRelatorio $filtros): Collection
    {
        return BemImovel::query()
            ->with('planoContasRelacaoRef:id,codigo,titulo')
            ->where('Id_situacao', 1)
            ->when($filtros->conta, fn ($q) => $q->where('id_planocontas', $filtros->conta))
            ->get();
    }

    /**
     * Reavaliacoes por imovel, ordenadas da mais recente para a mais antiga.
     *
     * @param  list<int>  $imovelIds
     * @return Collection<int, Collection<int, Reavaliacao>>  keyBy Id_imovel
     */
    protected function reavaliacoesPorImovel(array $imovelIds): Collection
    {
        return Reavaliacao::query()
            ->whereIn('Id_imovel', $imovelIds)
            ->orderByDesc('data_reavaliacao')
            ->get(['Id_imovel', 'data_reavaliacao', 'valor_reavaliacao', 'ajuste_contabil'])
            ->groupBy('Id_imovel');
    }

    /**
     * Obras por imovel.
     *
     * @param  list<int>  $imovelIds
     * @return Collection<int, Collection<int, Obra>>  keyBy id_imovel
     */
    protected function obrasPorImovel(array $imovelIds): Collection
    {
        return Obra::query()
            ->whereIn('id_imovel', $imovelIds)
            ->get(['id_imovel', 'valor', 'data'])
            ->groupBy('id_imovel');
    }

    /**
     * Legado: (imo.data_baixa >= :limite OR imo.data_baixa = '0000-00-00 00:00:00' OR imo.data_baixa IS NULL)
     */
    protected function naoBaixadoAte(BemImovel $imovel, Carbon $limite): bool
    {
        $dataBaixa = $imovel->data_baixa;

        return $dataBaixa === null
            || $dataBaixa === self::ZERO_DATE
            || $dataBaixa === '0000-00-00'
            || Carbon::parse($dataBaixa)->gte($limite);
    }
}
