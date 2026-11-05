<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Pages\Monitoramento;

class VolumePorContextoChart extends GraficoDoMonitoramento
{
    protected ?string $heading = 'Requisições por minuto, por área';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $serie = $this->repositorio()->serieVolume($this->periodoAtual(), $this->agora());
        $comData = $this->periodoAtual()->usaHoras();
        $presentes = $serie === [] ? [] : array_keys($serie[0]->valores);
        $contextos = array_values(array_filter(array_keys(Monitoramento::CONTEXTOS), fn ($c) => in_array($c, $presentes, true)));

        return [
            'labels' => array_map(fn ($p) => self::rotuloDoInstante($p->instante, $comData), $serie),
            'datasets' => array_map(fn (string $c) => self::barra(
                Monitoramento::CONTEXTOS[$c],
                array_map(fn ($p) => round($p->valores[$c] ?? 0, 2), $serie),
                self::corDoContexto($c),
            ), $contextos),
        ];
    }

    protected function getOptions(): array
    {
        $opcoes = parent::getOptions();
        $opcoes['scales']['x']['stacked'] = true;
        $opcoes['scales']['y']['stacked'] = true;

        return $opcoes;
    }
}
