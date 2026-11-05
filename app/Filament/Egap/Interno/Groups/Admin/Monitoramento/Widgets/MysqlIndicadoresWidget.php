<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\Concerns\WidgetDoMonitoramento;
use App\Monitoramento\Fontes\StatusDoMysql;
use App\Monitoramento\Leitura\Periodo;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MysqlIndicadoresWidget extends StatsOverviewWidget
{
    use WidgetDoMonitoramento;

    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '30s';

    protected int | array | null $columns = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $mysql = StatusDoMysql::ler();

        if ($mysql === null) {
            return [Stat::make('MySQL', 'indisponível')->description('Sem acesso ao status do servidor (permissão ou conexão).')];
        }

        $usoConexoes = $mysql['max_connections'] > 0 ? $mysql['threads_connected'] / $mysql['max_connections'] : 0;
        $acerto = $mysql['innodb_buffer_pool_read_requests'] > 0
            ? (1 - $mysql['innodb_buffer_pool_reads'] / $mysql['innodb_buffer_pool_read_requests']) * 100
            : null;

        return [
            Stat::make('Conexões em uso', $mysql['threads_connected'].' / '.$mysql['max_connections'])
                ->description('pico desde o início: '.$mysql['max_used_connections'])
                ->color($usoConexoes > 0.9 ? 'danger' : ($usoConexoes > 0.7 ? 'warning' : null)),
            Stat::make('Consultas em execução', (string) $mysql['threads_running']),
            Stat::make('Consultas por segundo', $this->consultasPorSegundo())
                ->description('média entre as duas últimas coletas'),
            Stat::make('Acerto do buffer pool', $acerto === null ? '—' : number_format($acerto, 2, ',', '.').'%')
                ->description('leituras atendidas pela memória, sem ir ao disco'),
            Stat::make('Esperas por trava de linha', number_format($mysql['innodb_row_lock_waits'], 0, ',', '.'))
                ->description('desde que o MySQL subiu ('.intdiv($mysql['uptime'], 3600).' h)'),
            $this->logLento($mysql),
        ];
    }

    private function consultasPorSegundo(): string
    {
        $snapshots = array_values(array_filter(
            $this->repositorio()->serieSnapshots(Periodo::Q15, $this->agora()),
            fn ($s) => $s->mysql !== null
        ));

        if (count($snapshots) < 2) {
            return '—';
        }

        [$antes, $depois] = array_slice($snapshots, -2);
        $segundos = $depois->instante - $antes->instante;
        $consultas = $depois->mysql['questions'] - $antes->mysql['questions'];

        return $segundos > 0 && $consultas >= 0 ? number_format($consultas / $segundos, 1, ',', '.') : '—';
    }

    private function logLento(array $mysql): Stat
    {
        $ok = $mysql['slow_query_log'] && $mysql['long_query_time'] <= 2;

        return Stat::make('Log de consultas lentas', $mysql['slow_query_log'] ? 'ligado' : 'desligado')
            ->description('limite '.rtrim(rtrim(number_format($mysql['long_query_time'], 2, ',', ''), '0'), ',').' s'.($ok ? '' : ' · recomendado: ligado e até 2 s'))
            ->color($ok ? null : 'warning');
    }
}
