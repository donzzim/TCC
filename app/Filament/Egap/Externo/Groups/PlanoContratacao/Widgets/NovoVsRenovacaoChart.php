<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Widgets;

use App\Filament\Egap\Externo\Groups\PlanoContratacao\Enums\SituacaoPlano;
use App\Filament\Egap\Externo\Pages\Widgets\BaseChart;
use App\Models\egap\PlanoContratacao\BemServico;
use App\Models\egap\PlanoContratacao\PlanoContratacao;
use Illuminate\Support\Facades\DB;

/**
 * "Novos Projetos vs Projetos Renovados" (legado: `pla_graficospac` aba
 * "N° Itens / Valor total previsto"). Compara valor previsto (`SUM(valor_final)`)
 * de itens Validados (id_etapa 7), por classificação × renovação de contrato.
 */
class NovoVsRenovacaoChart extends BaseChart
{
    protected ?string $heading = 'Novos vs Renovados — valor previsto';

    public ?string $filter = null;

    public function getColumnSpan(): string|int|array
    {
        return 1;
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
            ->where('pp.ano_execucao', $ano)
            ->where('egap_pla_bensservicos.id_etapa', SituacaoPlano::Validado->value)
            ->groupBy('pp.classificacao', 'egap_pla_bensservicos.renovacao_contrato')
            ->select([
                'pp.classificacao',
                'egap_pla_bensservicos.renovacao_contrato',
                DB::raw('SUM(egap_pla_bensservicos.valor_final) as total'),
            ])
            ->get();

        $valor = fn (string $classificacao, string $renovacao): float => (float) $linhas
            ->firstWhere(fn ($linha): bool => $linha->classificacao === $classificacao && $linha->renovacao_contrato === $renovacao)?->total;

        return [
            'datasets' => [
                [
                    'label' => 'Novo',
                    'data' => [$valor('investimento', 'nao'), $valor('custeio', 'nao')],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                ],
                [
                    'label' => 'Renovação',
                    'data' => [$valor('investimento', 'sim'), $valor('custeio', 'sim')],
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgba(16, 185, 129, 1)',
                ],
            ],
            'labels' => ['Investimento', 'Custeio'],
        ];
    }
}
