<?php

namespace App\Http\Controllers\Egap\MobileApi;

use App\Filament\Egap\Auth\MobileEgapSession;
use App\Http\Controllers\Controller;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\SituacaoBemMovel;
use App\Services\Egap\Mobile\ConferenciaBensService;
use App\Services\Egap\Mobile\PedidosMobileService;
use Illuminate\Http\JsonResponse;
use Throwable;

class DashboardController extends Controller
{
    public function show(
        MobileEgapSession $sessao,
        ConferenciaBensService $conferenciaBensService,
        PedidosMobileService $pedidosService,
    ): JsonResponse {
        $scope = $sessao->escopo();

        $baseQuery = fn () => BemMovel::query()
            ->where('UnidadeJudiciaria', $scope['unidade_judiciaria'])
            ->where('Setor', $scope['setor'])
            ->whereIn('SituacaoBem', BensController::SITUACOES_ELEGIVEIS);

        // selectRaw necessario: SUM condicional (CASE WHEN) e COUNT agrupado
        // nao tem equivalente fluente no query builder do Eloquent.
        $totais = $baseQuery()
            ->selectRaw('
                COUNT(*) as total,
                COALESCE(SUM(ValorAquisicao), 0) as valor_aquisicao,
                COALESCE(SUM(Valor), 0) as valor_atual,
                COALESCE(SUM(CASE WHEN ValorAquisicao IS NULL AND Valor IS NULL THEN 1 ELSE 0 END), 0) as sem_valor
            ')
            ->first();
        $situacoes = $baseQuery()
            ->selectRaw('SituacaoBem as situacao_id, COUNT(*) as total')
            ->groupBy('SituacaoBem')
            ->orderByDesc('total')
            ->get();
        $situacaoLabels = SituacaoBemMovel::query()
            ->whereIn('id', $situacoes->pluck('situacao_id')->filter()->values())
            ->get(['id', 'descricao', 'situacao'])
            ->keyBy('id');

        try {
            $conferencia = $conferenciaBensService->atual($scope);
        } catch (Throwable) {
            $conferencia = null;
        }

        return response()->json([
            'bens' => [
                'total' => (int) ($totais?->total ?? 0),
                'situacoes' => $situacoes
                    ->map(fn ($row): array => [
                        'id' => $row->situacao_id,
                        'label' => $situacaoLabels->get($row->situacao_id)?->descricao_completa ?? 'Sem situação',
                        'total' => (int) $row->total,
                    ])
                    ->values(),
            ],
            'financeiro' => [
                'valor_aquisicao' => (float) ($totais?->valor_aquisicao ?? 0),
                'valor_atual' => (float) ($totais?->valor_atual ?? 0),
                'sem_valor' => (int) ($totais?->sem_valor ?? 0),
            ],
            'conferencia' => $conferencia,
            'pedidos_abertos' => $pedidosService->contarAbertos($scope),
        ]);
    }
}
