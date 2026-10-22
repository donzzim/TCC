<?php

namespace App\Filament\Widgets\Escola;

use App\Services\Pedagogico\DashboardPedagogicoService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class DashboardIndicadoresMensaisChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Evolucao mensal dos indicadores';
    protected ?string $description = 'Acoes, participantes, aprovados e reprovados por mes';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 4;
    protected ?string $pollingInterval = '100s';
    protected ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $series = app(DashboardPedagogicoService::class)->seriesMensais($this->filters);

        return [
            'datasets' => [
                [
                    'label' => 'Acoes',
                    'data' => $series['acoes'],
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.16)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Participantes',
                    'data' => $series['participantes'],
                    'borderColor' => 'rgb(14, 165, 233)',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.12)',
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Aprovados',
                    'data' => $series['aprovados'],
                    'borderColor' => 'rgb(245, 158, 11)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.14)',
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Reprovados',
                    'data' => $series['reprovados'],
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.12)',
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
