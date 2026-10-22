<?php

namespace App\Filament\Egap\Externo\Pages\Widgets\Almoxarifado;

use App\Filament\Egap\Externo\Pages\Widgets\BaseChart;
use App\Models\egap\Almoxarifado\Pedidos;

class RequisicoesBensConsumoAtendidas extends BaseChart
{
    protected ?string $heading = 'Requisições de Bens de Consumo Atendidas por Ano';

    protected function getData(): array
    {
        $registros = $this->aggregateByYear(
            Pedidos::query()->where('setor_responsavel', 799),
            'date_time',
            ['qtde' => 'COUNT(*)'],
        );

        $labels = $registros->pluck('ano')
            ->map(fn($v) => (string) $v)
            ->toArray();

        $quantidades = $registros->pluck('qtde')
            ->map(fn($v) => (int) $v)
            ->toArray();

        $colors = $this->getColors(count($quantidades));
        $border_colors = $this->getBorderColors(count($quantidades));

        if ($this->chartType === 'bubble') {
            return [
                'datasets' => [
                    [
                        'label' => 'Pedidos por ano',
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
                        'label' => 'Pedidos por ano',
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
                        'label' => 'Pedidos por ano',
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
                    'label' => 'Pedidos por ano',
                    'data' => $quantidades,
                    'backgroundColor' => $colors,
                    'borderColor' => $border_colors,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
