<?php

namespace App\Filament\Egap\Pages\Widgets\EgapDashboard;

use App\Filament\Egap\Pages\EgapDashboard;
use App\Services\Egap\PatrimonioDashboardService;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class PatrimonioImoveisPorContaChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Imóveis por conta contábil';

    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = app(PatrimonioDashboardService::class)->getImoveisPorContaContabil($this->pageFilters);

        return [
            'datasets' => [
                [
                    'label' => 'Quantidade',
                    'data' => $data['values'],
                    'backgroundColor' => EgapDashboard::getColors(Color::Emerald, count($data['values'])),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
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
