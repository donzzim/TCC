<?php

namespace App\Filament\Egap\Externo\Pages\Widgets\Patrimonio;

use App\Filament\Egap\Externo\Pages\Widgets\BaseChart;
use App\Models\egap\Patrimonio\BensMoveis\TransferenciaBemMovel;

class BensPatrimoniaisMovimentados extends BaseChart
{
    protected ?string $heading = 'Bens Patrimoniais Movimentados por Ano';

    protected function getData(): array
    {
        $registros = $this->aggregateByYear(
            TransferenciaBemMovel::query()->whereYear('date_time', '>', 2016),
            'date_time',
            ['qtde' => 'COUNT(*)'],
        );

        $labels = $registros->pluck('ano')->map(fn($value) => (string) $value)->toArray();
        $quantidades = $registros->pluck('qtde')->map(fn($value) => (int) $value)->toArray();
        $colors = $this->getColors(count($quantidades));
        $border_colors = $this->getBorderColors(count($quantidades));

        if ($this->chartType === 'bubble') {
            return [
                'datasets' => [
                    [
                        'label' => 'Quantidade de transferências',
                        'data' => $registros->map(function ($item) {
                            $qtde = max((int) $item->qtde, 1);

                            return [
                                'x' => (int) $item->ano,
                                'y' => $qtde,
                                'r' => max(5, min(25, (int) round(sqrt($qtde) * 1.5))),
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
                        'label' => 'Quantidade de transferências',
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
                        'label' => 'Quantidade de transferências',
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
                    'label' => 'Quantidade de transferências',
                    'data' => $quantidades,
                    'backgroundColor' => $colors,
                    'borderColor' => $border_colors,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
