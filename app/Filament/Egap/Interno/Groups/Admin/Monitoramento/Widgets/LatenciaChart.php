<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

class LatenciaChart extends GraficoDoMonitoramento
{
    protected ?string $heading = 'Tempo de resposta (ms)';

    protected ?string $description = 'p50 = metade das requisições abaixo disso; p95 = 95% abaixo disso.';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $serie = $this->repositorio()->serieLatencia($this->periodoAtual(), $this->contexto, $this->agora());
        $comData = $this->periodoAtual()->usaHoras();

        return [
            'labels' => array_map(fn ($p) => self::rotuloDoInstante($p->instante, $comData), $serie),
            'datasets' => [
                self::linha('p50', array_map(fn ($p) => $p->valores['p50'], $serie), self::CORES[0]),
                self::linha('p95', array_map(fn ($p) => $p->valores['p95'], $serie), self::CORES[1]),
            ],
        ];
    }
}
