<?php

namespace App\Filament\Widgets\Escola;

use App\Services\Auto\AutoDashboardService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AutoDashboardStats extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $summary = app(AutoDashboardService::class)->summary($this->filters);

        return [
            Stat::make('Cursos automatizados', number_format($summary['total'], 0, ',', '.'))
                ->description('Cursos com automação ativa')
                ->descriptionIcon('heroicon-o-cog-6-tooth')
                ->color('info'),
            Stat::make('Aguardando ação', number_format($summary['waiting'], 0, ',', '.'))
                ->description('Pausas ou pendências globais')
                ->descriptionIcon('heroicon-o-pause-circle')
                ->color('warning'),
            Stat::make('Com erro', number_format($summary['errors'], 0, ',', '.'))
                ->description('Cursos que exigem tratamento')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),
            Stat::make('Concluídos', number_format($summary['completed'], 0, ',', '.'))
                ->description('Sem etapas pendentes')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Pendências bloqueantes', number_format($summary['blocking'], 0, ',', '.'))
                ->description('Podem interromper o fluxo')
                ->descriptionIcon('heroicon-o-hand-raised')
                ->color('warning'),
            Stat::make('Etapas prontas', number_format($summary['ready_steps'], 0, ',', '.'))
                ->description('Aguardando despacho')
                ->descriptionIcon('heroicon-o-play-circle')
                ->color('primary'),
        ];
    }
}
