<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\Concerns\WidgetDoMonitoramento;
use App\Monitoramento\Fontes\UsuariosOnline;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IndicadoresGeraisWidget extends StatsOverviewWidget
{
    use WidgetDoMonitoramento;

    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '5s';

    protected int | array | null $columns = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $agora = $this->agora();
        $periodo = $this->periodoAtual();
        $repo = $this->repositorio();
        $resumo = $repo->resumo($periodo, $this->contexto, $agora);
        $anterior = $repo->resumo($periodo, $this->contexto, $agora - $periodo->segundos());
        $snapshot = $repo->ultimoSnapshot();

        return [
            $this->online(UsuariosOnline::contar($agora)),
            Stat::make('Requisições por minuto', number_format($resumo->reqPorMin, 1, ',', '.'))
                ->description(self::variacao($resumo->reqPorMin, $anterior->reqPorMin).' · '.number_format($resumo->qtd, 0, ',', '.').' no período'),
            Stat::make('Tempo de resposta (p95)', self::ms($resumo->p95))
                ->description('p50 '.self::ms($resumo->p50).' · anterior '.self::ms($anterior->p95))
                ->color($resumo->p95 !== null && $resumo->p95 > 1000 ? 'warning' : null),
            Stat::make('Erros 5xx', number_format($resumo->taxaErro5xx * 100, 2, ',', '.').'%')
                ->description($resumo->erros5xx.' erros · '.$resumo->erros4xx.' respostas 4xx')
                ->color($resumo->taxaErro5xx > 0.01 ? 'danger' : null),
            $this->fila($snapshot?->fila),
            $this->mysql($snapshot?->mysql),
        ];
    }

    private function online(?array $online): Stat
    {
        if ($online === null) {
            return Stat::make('Usuários online', 'indisponível');
        }

        return Stat::make('Usuários online', (string) array_sum($online))
            ->description($online['web_interno'].' web interno · '.$online['web_externo'].' web externo · '.$online['app'].' app');
    }

    private function fila(?array $fila): Stat
    {
        if ($fila === null) {
            return Stat::make('Fila de jobs', 'indisponível')->description('aguardando a coleta (a cada minuto)');
        }

        $worker = match ($fila['worker']['running'] ?? null) {
            true => 'worker ativo',
            false => 'worker parado',
            default => 'worker desconhecido',
        };

        return Stat::make('Fila de jobs', number_format($fila['pendentes'], 0, ',', '.').' pendentes')
            ->description($fila['falhos_24h'].' falhos em 24 h · '.$worker)
            ->color(($fila['worker']['running'] ?? null) === false && $fila['pendentes'] > 0 ? 'danger' : null);
    }

    private function mysql(?array $mysql): Stat
    {
        if ($mysql === null) {
            return Stat::make('MySQL', 'indisponível')->description('aguardando a coleta (a cada minuto)');
        }

        return Stat::make('Conexões MySQL', $mysql['threads_connected'].' / '.$mysql['max_connections'])
            ->description($mysql['threads_running'].' consultas em execução · pico '.$mysql['max_used_connections']);
    }

    private static function variacao(float $atual, float $anterior): string
    {
        if ($anterior <= 0) {
            return 'sem período anterior';
        }

        $delta = ($atual - $anterior) / $anterior * 100;

        return ($delta >= 0 ? '+' : '').number_format($delta, 0, ',', '.').'% vs. período anterior';
    }
}
