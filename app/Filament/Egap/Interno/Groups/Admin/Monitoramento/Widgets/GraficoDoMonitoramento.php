<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Pages\Monitoramento;
use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\Concerns\WidgetDoMonitoramento;
use Filament\Widgets\ChartWidget;

abstract class GraficoDoMonitoramento extends ChartWidget
{
    use WidgetDoMonitoramento;

    public const CORES = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#6250d6', '#e34948'];

    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '5s';

    protected ?string $maxHeight = '260px';

    protected int | string | array $columnSpan = 1;

    protected static function corDoContexto(string $contexto): string
    {
        $indice = array_search($contexto, array_keys(Monitoramento::CONTEXTOS), true);

        return self::CORES[$indice === false ? count(self::CORES) - 1 : $indice];
    }

    protected static function linha(string $rotulo, array $valores, string $cor): array
    {
        return [
            'label' => $rotulo,
            'data' => $valores,
            'borderColor' => $cor,
            'backgroundColor' => $cor,
            'borderWidth' => 2,
            'pointRadius' => 0,
            'pointHoverRadius' => 4,
            'spanGaps' => false,
            'tension' => 0.2,
        ];
    }

    protected static function barra(string $rotulo, array $valores, string $cor): array
    {
        return [
            'label' => $rotulo,
            'data' => $valores,
            'backgroundColor' => $cor,
            'borderColor' => 'rgba(0,0,0,0)',
            'borderWidth' => 1,
            'borderRadius' => 4,
            'maxBarThickness' => 18,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'animation' => ['duration' => 0],
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => ['legend' => ['display' => true, 'position' => 'bottom']],
            'scales' => [
                'x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 8]],
                'y' => ['beginAtZero' => true, 'grid' => ['color' => 'rgba(127,127,127,0.15)']],
            ],
        ];
    }
}
