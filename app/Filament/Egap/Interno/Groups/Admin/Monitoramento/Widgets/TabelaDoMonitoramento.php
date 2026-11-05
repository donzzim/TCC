<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\Concerns\WidgetDoMonitoramento;
use Filament\Widgets\TableWidget;

abstract class TabelaDoMonitoramento extends TableWidget
{
    use WidgetDoMonitoramento;

    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '30s';

    protected int | string | array $columnSpan = 'full';

    protected static function numero(float | int | null $valor, int $casas = 0): string
    {
        return $valor === null ? '—' : number_format((float) $valor, $casas, ',', '.');
    }
}
