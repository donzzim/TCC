<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

class ErrosChart extends GraficoDoMonitoramento
{
    protected ?string $heading = 'Respostas com erro';

    protected ?string $description = '5xx = falha no servidor; 4xx = pedido inválido, sem permissão ou não encontrado.';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $serie = $this->repositorio()->serieErros($this->periodoAtual(), $this->contexto, $this->agora());
        $comData = $this->periodoAtual()->usaHoras();

        return [
            'labels' => array_map(fn ($p) => self::rotuloDoInstante($p->instante, $comData), $serie),
            'datasets' => [
                self::barra('5xx', array_map(fn ($p) => $p->valores['5xx'], $serie), self::CORES[0]),
                self::barra('4xx', array_map(fn ($p) => $p->valores['4xx'], $serie), self::CORES[1]),
            ],
        ];
    }
}
