<?php

namespace App\Filament\Egap\Pages\Widgets\EgapDashboard;

use App\Services\Egap\PatrimonioDashboardService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class PatrimonioOverviewStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $service = app(PatrimonioDashboardService::class);
        $summary = $service->getSummary($this->pageFilters);
        $movableByYear = $service->getMoveisPorAno($this->pageFilters);
        $trend = $movableByYear['values'] !== [] ? $movableByYear['values'] : [0];
        $periodo = $service->getPeriodoDescricao($this->pageFilters);

        return [
            Stat::make('Bens móveis', Number::format($summary['moveis_total'], locale: 'pt_BR'))
                ->description($periodo)
                ->descriptionIcon('heroicon-o-cube')
                ->chart($trend)
                ->color('info'),

            Stat::make('Bens imóveis', Number::format($summary['imoveis_total'], locale: 'pt_BR'))
                ->description($periodo)
                ->descriptionIcon('heroicon-o-home-modern')
                ->chart($trend)
                ->color('success'),

            Stat::make('Móveis ativos', Number::format($summary['moveis_ativos_total'], locale: 'pt_BR'))
                ->description('Situações operacionais 1, 7 e 8')
                ->descriptionIcon('heroicon-o-check-badge')
                ->chart($trend)
                ->color('warning'),

            Stat::make('Aquisição de moveis', Number::currency($summary['moveis_valor_aquisicao'], 'BRL', locale: 'pt_BR'))
                ->description('Somatório do valor de aquisição')
                ->descriptionIcon('heroicon-o-banknotes')
                ->chart($trend)
                ->color('primary'),
        ];
    }
}
