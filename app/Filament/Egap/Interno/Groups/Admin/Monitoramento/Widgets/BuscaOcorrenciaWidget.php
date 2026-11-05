<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\Concerns\WidgetDoMonitoramento;
use App\Monitoramento\Logs\LeitorDeLog;
use Filament\Widgets\Widget;

class BuscaOcorrenciaWidget extends Widget
{
    use WidgetDoMonitoramento;

    protected static bool $isDiscovered = false;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'egap.monitoramento.busca-ocorrencia';

    public string $codigo = '';

    public bool $buscou = false;

    public ?array $resultado = null;

    public function buscar(): void
    {
        $entrada = (new LeitorDeLog)->buscarOcorrencia($this->codigo);

        $this->buscou = true;
        $this->resultado = $entrada === null ? null : [
            'data' => $entrada->data,
            'nivel' => $entrada->nivel,
            'mensagem' => $entrada->mensagem,
            'contexto' => $entrada->contexto ?? [],
            'trace' => $entrada->trace,
            'arquivo' => basename($entrada->arquivo),
        ];
    }
}
