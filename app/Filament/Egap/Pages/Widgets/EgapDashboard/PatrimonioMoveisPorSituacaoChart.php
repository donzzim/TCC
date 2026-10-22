<?php

namespace App\Filament\Egap\Pages\Widgets\EgapDashboard;

use App\Filament\Egap\Pages\EgapDashboard;
use App\Services\Egap\PatrimonioDashboardService;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class PatrimonioMoveisPorSituacaoChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Bens móveis por situação';

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 2;

    protected function getData(): array
    {
        $data = app(PatrimonioDashboardService::class)->getMoveisPorSituacao($this->pageFilters);

        return [
            'datasets' => [
                [
                    'label' => 'Quantidade',
                    'data' => $data['values'],
                    'backgroundColor' => EgapDashboard::getColors(Color::Sky, count($data['values'])),
                    'borderWidth' => 0,
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
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
