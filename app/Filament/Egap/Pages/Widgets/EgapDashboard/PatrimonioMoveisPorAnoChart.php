<?php

namespace App\Filament\Egap\Pages\Widgets\EgapDashboard;

use App\Filament\Egap\Pages\EgapDashboard;
use App\Services\Egap\PatrimonioDashboardService;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class PatrimonioMoveisPorAnoChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Bens móveis por ano de incorporação';

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 2;

    protected function getData(): array
    {
        $data = app(PatrimonioDashboardService::class)->getMoveisPorAno($this->pageFilters);

        return [
            'datasets' => [
                [
                    'label' => 'Quantidade',
                    'data' => $data['values'],
                    'backgroundColor' => EgapDashboard::getColors(Color::Blue, count($data['values'])),
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
