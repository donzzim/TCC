<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensImoveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensImoveis\BemImovel;
use App\Models\egap\Patrimonio\BensImoveis\Obra;
use App\Models\egap\Patrimonio\BensImoveis\Reavaliacao;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Relatorio "Relacao dos Bens Imoveis".
 *
 * Legado: relatorios-prints-queries/bens-imoveis/relacao-dos-bens-imoveis.php
 *
 *   ... LEFT JOIN (select Id_imovel, valor_reavaliacao from imo_reavaliacao
 *                  WHERE data_reavaliacao <= :termino ORDER BY data_reavaliacao DESC LIMIT 1) rea
 *   ... LEFT JOIN (SELECT SUM(valor) valor_obra, id_imovel FROM imo_obras
 *                  WHERE data <= :termino GROUP BY id_imovel) obras
 *   WHERE (imo.data_baixa IS NULL OR imo.data_baixa = '0000-00-00 00:00:00')
 *   ORDER BY imo.id_situacao, imo.Id_planocontas
 *
 * BUG-LEGADO: a subquery `rea` NAO e correlacionada e tem LIMIT 1 -> retorna
 * UMA linha global (a reavaliacao mais recente <= termino em todo o cadastro),
 * que so casa com o proprio imovel dela via ON imo.id = rea.Id_imovel.
 * Para os demais imoveis, valor_reavaliado = imo.valor_reavaliado. Mantido.
 *
 * BUG-LEGADO: o filtro de conta ($filtro com alias `p.ContaContabil`) e montado
 * mas nunca aplicado a query. Mantido (sem filtro de conta).
 */
final class RelacaoBensImoveisService implements RelatorioService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $terminoData = $filtros->fimData();

        $reaGlobal = Reavaliacao::query()
            ->whereDate('data_reavaliacao', '<=', $terminoData)
            ->orderByDesc('data_reavaliacao')
            ->first(['Id_imovel', 'valor_reavaliacao']);

        $obraPorImovel = Obra::query()
            ->whereDate('data', '<=', $terminoData)
            ->groupBy('id_imovel')
            ->select('id_imovel')
            ->selectRaw('SUM(valor) as valor_obra')
            ->pluck('valor_obra', 'id_imovel');

        return BemImovel::query()
            ->with([
                'estadoConservacaoRelacaoRef',
                'planoContasRelacaoRef:id,codigo',
                'situacaoRelacaoRef',
            ])
            ->where(fn ($q) => $q->whereNull('data_baixa')->orWhere('data_baixa', '0000-00-00 00:00:00'))
            ->orderBy('id_situacao')
            ->orderBy('Id_planocontas')
            ->get()
            ->map(function (BemImovel $imo) use ($reaGlobal, $obraPorImovel): array {
                $valorReavaliado = ($reaGlobal !== null && (int) $reaGlobal->Id_imovel === (int) $imo->Id)
                    ? (float) $reaGlobal->valor_reavaliacao
                    : (float) $imo->valor_reavaliado;

                $valorObra = (float) ($obraPorImovel[$imo->Id] ?? 0);

                return [
                    'num_registro' => $imo->num_registro,
                    'descricao' => $imo->descricao,
                    'data_aquisicao' => $this->data($imo->data_aquisicao),
                    'data_construcao' => $this->data($imo->data_construcao),
                    'data_incorporacao' => $this->data($imo->data_incorporacao),
                    'estado_conservacao' => $imo->estadoConservacaoRelacaoRef?->descEstadoConservacao,
                    'conta_codigo' => $imo->planoContasRelacaoRef?->codigo,
                    'data_ingresso_contabil' => $this->data($imo->data_ingresso_contabil),
                    'inscricao_generica' => $imo->inscricao_generica,
                    'endereco' => $this->endereco($imo),
                    'area' => $imo->area,
                    'area_edificacao' => $imo->area_edificacao,
                    'vida_util' => $imo->vida_util,
                    'data_reavaliacao' => $this->data($imo->data_reavaliacao),
                    'valor_historico' => (float) $imo->valor_historico_1a_avaliacao,
                    'conta' => $imo->situacaoRelacaoRef?->Descricao,
                    'valor_reavaliado' => $valorReavaliado,
                    'valor_obra' => $valorObra,
                    'valor_atualizado' => $valorReavaliado + $valorObra,
                ];
            })
            ->values();
    }

    private function data(mixed $valor): ?string
    {
        if (blank($valor) || $valor === '0000-00-00' || $valor === '0000-00-00 00:00:00') {
            return null;
        }

        return Carbon::parse($valor)->format('d/m/Y');
    }

    private function endereco(BemImovel $imo): string
    {
        return collect([
            $imo->end_logradouro,
            $imo->end_numero,
            $imo->end_bairro,
            $imo->end_cidade,
            $imo->end_estado,
            $imo->end_compl_endereco,
        ])->filter(fn ($p) => $p !== null && $p !== '')->join(', ');
    }
}
