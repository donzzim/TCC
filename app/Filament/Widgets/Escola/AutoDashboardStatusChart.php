<?php

namespace App\Filament\Widgets\Escola;

use App\Services\Auto\AutoDashboardService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class AutoDashboardStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;
    protected ?string $heading = 'Cursos por estado da automação';
    protected ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan = 6;

    protected function getData(): array
    {
        $data = app(AutoDashboardService::class)->statusDistribution($this->filters);

        return [
            'datasets' => [[
                'label' => 'Cursos',
                'data' => $data['data'],
                'backgroundColor' => [
                    '#0ea5e9', '#f59e0b', '#ef4444', '#22c55e',
                    '#64748b', '#14b8a6', '#94a3b8',
                ],
                'borderWidth' => 0,
            ]],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
