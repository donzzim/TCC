<?php

namespace App\Filament\Egap\Externo\Pages\Widgets\Almoxarifado;

use App\Filament\Egap\Externo\Pages\Widgets\BaseChart;
use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use Filament\Support\RawJs;

class ExecucaoOrcamentariaAlmoxarifado extends BaseChart
{
    protected ?string $heading = 'Execução Orçamentária do Almoxarifado';

    protected function getData(): array
    {
        $registros = $this->aggregateByYear(
            MovimentacaoEstoque::query()
                ->where('tipo_movimentacao', 2)
                ->whereHas('materialRel', fn($query) => $query->where('item_estoque', 1))
                ->whereHas('materialRel.descricao_resumida_text.produto_id', fn($query) => $query->whereBetween('CodigodaClasse', [33000000, 33999999])),
            'date_time',
            ['valor' => 'SUM(quantidade * preco_unitario)'],
        );

        $labels = $registros->pluck('ano')
            ->map(fn($value) => (string) $value)
            ->toArray();

        $valores = $registros->pluck('valor')
            ->map(fn($value) => (float) $value)
            ->toArray();

        $colors = $this->getColors(count($valores));
        $border_colors = $this->getBorderColors(count($valores));

        if ($this->chartType === 'bubble') {
            return [
                'datasets' => [
                    [
                        'label' => 'Valor movimentado',
                        'data' => $registros->map(function ($item) {
                            $valor = max((float) $item->valor, 1);

                            return [
                                'x' => (int) $item->ano,
                                'y' => $valor,
                                'r' => max(5, min(25, (int) round($valor / 100000))),
                            ];
                        })->toArray(),
                        'backgroundColor' => $this->getColors($registros->count()),
                    ],
                ],
                'labels' => $labels,
            ];
        }

        if (in_array($this->chartType, ['pie', 'doughnut', 'polarArea'], true)) {
            return [
                'datasets' => [
                    [
                        'label' => 'Valor movimentado',
                        'data' => $valores,
                        'backgroundColor' => $colors,
                    ],
                ],
                'labels' => $labels,
            ];
        }

        if ($this->chartType === 'line') {
            return [
                'datasets' => [
                    [
                        'label' => 'Valor movimentado',
                        'data' => $valores,
                        'backgroundColor' => 'rgba(59, 130, 246, 0.12)',
                        'borderColor' => 'rgba(59, 130, 246, 1)',
                        'pointBackgroundColor' => $colors,
                        'tension' => 0.3,
                        'fill' => true,
                    ],
                ],
                'labels' => $labels,
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Valor movimentado',
                    'data' => $valores,
                    'backgroundColor' => $colors,
                    'borderColor' => $border_colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
    {
        scales: {
            y: {
                ticks: {
                    callback: function(value) {

                        function formatMilhao(valor) {
                            if (valor >= 1000000) {
                                return 'R$ ' + (valor / 1000000).toFixed(1).replace('.', ',') + ' mi';
                            }

                            if (valor >= 1000) {
                                return 'R$ ' + (valor / 1000).toFixed(1).replace('.', ',') + ' mil';
                            }

                            return new Intl.NumberFormat('pt-BR', {
                                style: 'currency',
                                currency: 'BRL'
                            }).format(valor);
                        }

                        return formatMilhao(value);
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }

                        const parsed = context.parsed;
                        const value = typeof parsed === 'number'
                            ? parsed
                            : (parsed?.y ?? 0);

                        return label + new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        }).format(value);
                    }
                }
            }
        }
    }
    JS);
    }
}
