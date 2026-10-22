<?php

namespace App\Filament\Widgets\Contratacao;

use App\Models\Contratacao\Orcamento;
use App\Models\Contratacao\ProcessoEvento;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsContratacao extends BaseWidget
{
    use HasWidgetShield;
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '100s';

    protected function getHeading(): ?string
    {
        return $this->heading;
    }

    public function gerarDados($tam)
    {
        $arr = [];
        for ($i = 0; $i < $tam; $i++) {
            $arr[] = fake()->numberBetween(0, 30);
        }
        return $arr;
    }

    protected function getStats(): array
    {
        $arrReserva = $this->gerarDados(12);
        $arrEmpenho = $this->gerarDados(12);
        $arrOrdem = $this->gerarDados(12);
        $arrLiquidacao = $this->gerarDados(12);

        $totalReserva = number_format(ProcessoEvento::where('tipoevento_id', '=', config('settings.processoevento.reserva'))->sum('valor'), 2, ',', '.');
        $totalEmpenho = number_format(ProcessoEvento::where('tipoevento_id', '=', config('settings.processoevento.empenho'))->sum('valor'), 2, ',', '.');
        $totalOrdem = number_format(ProcessoEvento::where('tipoevento_id', '=', config('settings.processoevento.ordemservico'))->sum('valor'), 2, ',', '.');
        $totalLiquidacao = number_format(ProcessoEvento::where('tipoevento_id', '=', config('settings.processoevento.liquidacao'))->sum('valor'), 2, ',', '.');

        return [
            Stat::make('Reserva', "R$ {$totalReserva}")
                ->description('total de reservas')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->chart($arrReserva)
                ->color('warning')
                ->extraAttributes(['class' => 'bg-gradient-to-r dark:from-orange-900 dark:to-orange-0 from-orange-100 to-orange-0']),

            Stat::make('Empenho', "R$ {$totalEmpenho}")
                ->description('total de empenhos')
                ->descriptionIcon('heroicon-o-document-currency-dollar')
                ->chart($arrEmpenho)
                ->color('primary')
                ->extraAttributes(['class' => 'bg-gradient-to-r dark:from-sky-900 dark:to-sky-0 from-sky-100 to-sky-0']),

            Stat::make('Orderm de Serviço', "R$ {$totalOrdem}")
                ->description('total de ordem de serviço')
                ->descriptionIcon('heroicon-o-banknotes')
                ->chart($arrOrdem)
                ->color('success')
                ->extraAttributes(['class' => 'bg-gradient-to-r dark:from-green-900 dark:to-green-0 from-green-100 to-green-0']),

            Stat::make('Liquidação', "R$ {$totalLiquidacao}")
                ->description('total de liquidação')
                ->descriptionIcon('heroicon-o-banknotes')
                ->chart($arrLiquidacao)
                ->color('info')
                ->extraAttributes(['class' => 'bg-gradient-to-r dark:from-blue-900 dark:to-blue-0 from-blue-100 to-blue-0']),
        ];
    }
}
