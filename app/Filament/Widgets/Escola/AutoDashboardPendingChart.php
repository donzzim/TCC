<?php

namespace App\Filament\Widgets\Escola;

use App\Services\Auto\AutoDashboardService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class AutoDashboardPendingChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;
    protected ?string $heading = 'Pendências por etapa';
    protected ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan = 6;

    protected function getData(): array
    {
        $data = app(AutoDashboardService::class)->pendingDistribution($this->filters);

        return [
            'datasets' => [[
                'label' => 'Pendencias',
                'data' => $data['data'],
                'backgroundColor' => '#f59e0b',
                'borderWidth' => 0,
            ]],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
