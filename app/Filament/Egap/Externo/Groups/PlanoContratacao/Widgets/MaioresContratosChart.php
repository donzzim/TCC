<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Widgets;

use App\Filament\Egap\Externo\Groups\PlanoContratacao\Enums\SituacaoPlano;
use App\Filament\Egap\Externo\Pages\Widgets\BaseChart;
use App\Models\egap\PlanoContratacao\BemServico;
use App\Models\egap\PlanoContratacao\PlanoContratacao;
use Illuminate\Support\Str;

/**
 * "Maiores Contratos" (legado: `pla_graficospac` aba "Maiores Contratos").
 * Top 10 itens Validados por valor final no ano.
 */
class MaioresContratosChart extends BaseChart
{
    protected ?string $heading = 'Maiores Contratos';

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

        $itens = BemServico::query()
            ->join('egap_pla_planocontratacao as pp', 'pp.id', '=', 'egap_pla_bensservicos.id_planocontratacao')
            ->where('pp.ano_execucao', $ano)
            ->where('egap_pla_bensservicos.id_etapa', SituacaoPlano::Validado->value)
            ->orderByDesc('egap_pla_bensservicos.valor_final')
            ->limit(10)
            ->select(['egap_pla_bensservicos.nome', 'egap_pla_bensservicos.valor_final'])
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Valor final',
                    'data' => $itens->pluck('valor_final')->map(fn ($v): float => (float) $v)->all(),
                    'backgroundColor' => $this->getColors($itens->count()),
                    'borderColor' => $this->getBorderColors($itens->count()),
                ],
            ],
            'labels' => $itens->pluck('nome')->map(fn (?string $nome): string => Str::limit((string) $nome, 40))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
