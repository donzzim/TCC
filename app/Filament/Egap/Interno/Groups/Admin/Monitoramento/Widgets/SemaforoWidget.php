<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\Concerns\WidgetDoMonitoramento;
use App\Monitoramento\Leitura\Periodo;
use App\Monitoramento\Leitura\SemaforoDeSaude;
use Filament\Widgets\Widget;

class SemaforoWidget extends Widget
{
    use WidgetDoMonitoramento;

    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '5s';

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'egap.monitoramento.semaforo';

    protected function getViewData(): array
    {
        $agora = $this->agora();
        $repo = $this->repositorio();

        return SemaforoDeSaude::avaliar(
            $repo->resumo(Periodo::Q15, $this->contexto, $agora),
            $repo->ultimoSnapshot(),
            $agora,
        );
    }
}
