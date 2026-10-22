<?php

namespace App\Filament\Widgets\Escola;

use App\Services\Pedagogico\DashboardPedagogicoService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardIndicadoresPedagogicosStats extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;
    protected ?string $pollingInterval = '100s';

    protected function getStats(): array
    {
        $service = app(DashboardPedagogicoService::class);
        $resumo = $service->resumo($this->filters);
        $series = $service->seriesMensais($this->filters);

        return [
            Stat::make('Acoes no periodo', number_format($resumo['acoes'], 0, ',', '.'))
                ->description('Cursos e eventos contabilizados uma vez')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->chart($series['acoes'])
                ->color('success'),

            Stat::make('Acoes ativas', number_format($resumo['acoes_ativas'], 0, ',', '.'))
                ->description('Cursos e eventos com status ativo')
                ->descriptionIcon('heroicon-o-calendar-days')
                // ->chart($series['acoes_ativas'])
                ->color('success'),

            Stat::make('Media mensal', number_format($resumo['media_acoes_mensal'], 2, ',', '.'))
                ->description('Acoes por mes no periodo')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->chart($series['acoes'])
                ->color('success'),

            Stat::make('Carga horaria', number_format($resumo['carga_horaria'], 2, ',', '.'))
                ->description('Total das acoes filtradas')
                ->descriptionIcon('heroicon-o-clock')
                ->chart($series['carga_horaria'])
                ->color('info'),

            Stat::make('Participantes', number_format($resumo['participantes'], 0, ',', '.'))
                ->description('Média de participantes por ação: ' . number_format($resumo['participantes'] / $resumo['acoes'], 1, ',', '.'))
                ->descriptionIcon('heroicon-o-user-group')
                ->chart($series['participantes'])
                ->color('warning'),

            Stat::make('Dias com atividades', number_format($resumo['dias_com_atividades'], 0, ',', '.'))
                ->description('Dias com pelo menos uma agenda')
                ->descriptionIcon('heroicon-o-calendar')
                ->chart($series['dias_com_atividades'])
                ->color('primary'),

            Stat::make('Aprovados', number_format($resumo['aprovados'], 0, ',', '.'))
                ->description('Taxa: ' . number_format($resumo['taxa_aprovacao'], 1, ',', '.') . '%')
                ->descriptionIcon('heroicon-o-check-circle')
                ->chart($series['aprovados'])
                ->color('success'),

            Stat::make('Reprovados', number_format($resumo['reprovados'], 0, ',', '.'))
                ->description('Taxa: ' . number_format($resumo['taxa_reprovacao'], 1, ',', '.') . '%')
                ->descriptionIcon('heroicon-o-x-circle')
                ->chart($series['reprovados'])
                ->color('danger'),

            Stat::make('Eventos especiais', number_format($resumo['eventos_especiais'], 0, ',', '.'))
                ->description('Noite, final de semana ou feriado')
                ->descriptionIcon('heroicon-o-moon')
                ->chart($series['eventos_especiais'])
                ->color('danger'),
        ];
    }
}
