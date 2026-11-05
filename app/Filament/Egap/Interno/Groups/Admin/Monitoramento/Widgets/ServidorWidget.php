<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\Concerns\WidgetDoMonitoramento;
use App\Monitoramento\Fontes\ChecklistDeConfiguracao;
use App\Monitoramento\Fontes\StatusDoServidor;
use Filament\Widgets\Widget;

class ServidorWidget extends Widget
{
    use WidgetDoMonitoramento;

    protected static bool $isDiscovered = false;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'egap.monitoramento.servidor';

    protected function getViewData(): array
    {
        $servidor = StatusDoServidor::ler();

        return [
            's' => $servidor,
            'checklist' => ChecklistDeConfiguracao::avaliar($servidor),
            'mb' => fn (?float $bytes): string => $bytes === null ? '—' : number_format($bytes / 1048576, 1, ',', '.').' MB',
            'gb' => fn (?float $bytes): string => $bytes === null ? '—' : number_format($bytes / 1073741824, 1, ',', '.').' GB',
        ];
    }
}
