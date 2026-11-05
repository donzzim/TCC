<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

class UsuariosOnlineChart extends GraficoDoMonitoramento
{
    protected ?string $heading = 'Usuários online';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $snapshots = $this->repositorio()->serieSnapshots($this->periodoAtual(), $this->agora());
        $comData = $this->periodoAtual()->usaHoras();
        $series = ['web_interno' => 'Web interno', 'web_externo' => 'Web externo', 'app' => 'App'];
        $datasets = [];
        $indice = 0;

        foreach ($series as $chave => $rotulo) {
            $datasets[] = self::linha($rotulo, array_map(fn ($s) => $s->online[$chave] ?? null, $snapshots), self::CORES[$indice++]);
        }

        return [
            'labels' => array_map(fn ($s) => self::rotuloDoInstante($s->instante, $comData), $snapshots),
            'datasets' => $datasets,
        ];
    }
}
