<?php

namespace App\Filament\Widgets\Escola;

use App\Services\Auto\AutoDashboardService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class AutoDashboardStepStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;
    protected ?string $heading = 'Etapas habilitadas por status';
    protected ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan = 6;

    protected function getData(): array
    {
        $data = app(AutoDashboardService::class)->stepStatusDistribution($this->filters);

        return [
            'datasets' => [[
                'label' => 'Etapas',
                'data' => $data['data'],
                'backgroundColor' => '#0ea5e9',
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
            'scales' => [
                'y' => [
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
