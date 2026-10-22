<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Widgets;

use App\Filament\Egap\Externo\Groups\PlanoContratacao\Enums\SituacaoPlano;
use App\Filament\Egap\Externo\Pages\Widgets\BaseChart;
use App\Models\egap\PlanoContratacao\BemServico;
use App\Models\egap\PlanoContratacao\PlanoContratacao;
use Illuminate\Support\Facades\DB;

/**
 * "Ranking de Setores" (legado: `pla_graficospac` aba "Gastos das Secretarias").
 * Top 15 setores por valor previsto de itens Validados no ano.
 */
class RankingSetoresChart extends BaseChart
{
    protected ?string $heading = 'Ranking de Setores — valor previsto';

    public ?string $filter = null;

    public function getColumnSpan(): string|int|array
    {
        return 'full';
    }

    protected function getFilters(): ?array
    {
        return collect(PlanoContratacao::anosDisponiveis())
            ->mapWithKeys(fn (int $ano): array => [(string) $ano => (string) $ano])
            ->all();
    }

    protected function getData(): array
    {
        $ano = (int) ($this->filter ?? PlanoContratacao::anosDisponiveis()[0] ?? (date('Y') + 1));

        $linhas = BemServico::query()
            ->join('egap_pla_planocontratacao as pp', 'pp.id', '=', 'egap_pla_bensservicos.id_planocontratacao')
            ->join('setores as ms', 'ms.id', '=', 'pp.cd_coordenadoria')
            ->where('pp.ano_execucao', $ano)
            ->where('egap_pla_bensservicos.id_etapa', SituacaoPlano::Validado->value)
            ->groupBy('ms.id', 'ms.setor')
            ->orderByDesc('total')
            ->limit(15)
            ->select([
                'ms.setor as setor',
                DB::raw('SUM(egap_pla_bensservicos.valor_final) as total'),
            ])
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Valor previsto',
                    'data' => $linhas->pluck('total')->map(fn ($v): float => (float) $v)->all(),
                    'backgroundColor' => $this->getColors($linhas->count()),
                    'borderColor' => $this->getBorderColors($linhas->count()),
                ],
            ],
            'labels' => $linhas->pluck('setor')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
