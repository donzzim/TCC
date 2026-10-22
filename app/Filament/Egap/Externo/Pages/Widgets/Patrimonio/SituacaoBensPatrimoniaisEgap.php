<?php

namespace App\Filament\Egap\Externo\Pages\Widgets\Patrimonio;

use App\Filament\Egap\Externo\Pages\Widgets\BaseChart;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;

class SituacaoBensPatrimoniaisEgap extends BaseChart
{
    protected ?string $heading = 'Situação Atual dos Bens Patrimoniais no Sistema E-Gap';

    protected function getData(): array
    {
        $registros = BemMovel::query()
            ->with('situacaoBemRef')
            ->get(['id', 'SituacaoBem'])
            ->groupBy('SituacaoBem')
            ->map(function ($bens, $situacaoId): object {
                $situacao = $bens->first()?->situacaoBemRef;

                return (object) [
                    'id' => (int) $situacaoId,
                    'situacao' => $situacao?->descricao ?? 'Sem situacao',
                    'qtde' => $bens->count(),
                ];
            })
            ->sortBy('situacao')
            ->values();

        $labels = $registros->pluck('situacao')->toArray();
        $quantidades = $registros->pluck('qtde')->map(fn($value) => (int) $value)->toArray();
        $colors = $this->getColors(count($quantidades));
        $border_colors = $this->getBorderColors(count($quantidades));

        if ($this->chartType === 'bubble') {
            return [
                'datasets' => [
                    [
                        'label' => 'Quantidade de bens por situação',
                        'data' => $registros->values()->map(function ($item, $index) {
                            $qtde = max((int) $item->qtde, 1);

                            return [
                                'x' => $index + 1,
                                'y' => $qtde,
                                'r' => max(5, min(25, (int) round(sqrt($qtde) * 1.5))),
                            ];
                        })->toArray(),
                        'backgroundColor' => $colors,
                    ],
                ],
                'labels' => $labels,
            ];
        }

        if (in_array($this->chartType, ['pie', 'doughnut', 'polarArea'], true)) {
            return [
                'datasets' => [
                    [
                        'label' => 'Quantidade de bens por situação',
                        'data' => $quantidades,
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
                        'label' => 'Quantidade de bens por situação',
                        'data' => $quantidades,
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
                    'label' => 'Quantidade de bens por situação',
                    'data' => $quantidades,
                    'backgroundColor' => $colors,
                    'borderColor' => $border_colors,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
