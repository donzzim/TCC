<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

use App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\Concerns\WidgetDoMonitoramento;
use App\Monitoramento\Fontes\StatusDaFila;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FilaWidget extends StatsOverviewWidget
{
    use WidgetDoMonitoramento;

    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '30s';

    protected int | array | null $columns = 4;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $fila = StatusDaFila::ler($this->agora());

        if ($fila === null) {
            return [Stat::make('Fila de jobs', 'indisponível')];
        }

        $porFila = collect($fila['por_fila'])->map(fn ($n, $nome) => "{$nome}: {$n}")->implode(' · ');
        $idade = $fila['idade_mais_antigo_seg'];
        $running = $fila['worker']['running'] ?? null;

        return [
            Stat::make('Jobs pendentes', number_format($fila['pendentes'], 0, ',', '.'))
                ->description($porFila !== '' ? $porFila : 'fila vazia'),
            Stat::make('Job mais antigo esperando', $idade === null ? '—' : self::duracao($idade))
                ->description('acima de 10 min indica fila parada')
                ->color($idade !== null && $idade > 600 ? 'warning' : null),
            Stat::make('Jobs falhos', number_format($fila['falhos_total'], 0, ',', '.'))
                ->description($fila['falhos_24h'].' nas últimas 24 h')
                ->color($fila['falhos_24h'] > 0 ? 'danger' : null),
            Stat::make('Worker da fila', match ($running) {
                true => 'worker ativo',
                false => 'worker parado',
                default => 'indisponível',
            })
                ->description(isset($fila['worker']['pid']) && $fila['worker']['pid'] ? 'PID '.$fila['worker']['pid'] : 'nenhum processo queue:work encontrado')
                ->color($running === false && $fila['pendentes'] > 0 ? 'danger' : null),
        ];
    }

    private static function duracao(int $segundos): string
    {
        return match (true) {
            $segundos < 60 => $segundos.' s',
            $segundos < 3600 => intdiv($segundos, 60).' min',
            $segundos < 86400 => intdiv($segundos, 3600).' h',
            default => intdiv($segundos, 86400).' dias',
        };
    }
}
