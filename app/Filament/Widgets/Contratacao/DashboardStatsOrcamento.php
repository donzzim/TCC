<?php

namespace App\Filament\Widgets\Contratacao;

use App\Models\Contratacao\Orcamento;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsOrcamento extends BaseWidget
{
    use InteractsWithPageFilters;
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
        $ano = $this->filters['ano'] ?? now()->year;

        $arrPrevisto = $this->gerarDados(12);
        $arrAprovado = $this->gerarDados(12);
        $arrAtual = $this->gerarDados(12);

        $totalPrevsto = number_format(Orcamento::where('ano', $ano)->sum('previsao_total'), 2, ',', '.');
        $totalAprovado = number_format(Orcamento::where('ano', $ano)->sum('valor_total'), 2, ',', '.');
        $totalAtual = number_format(Orcamento::where('ano', $ano)->sum('saldo_total'), 2, ',', '.');

        return [
            Stat::make('Previsto', "R$ {$totalPrevsto}")
                ->description('total de orçamento previsto')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->chart($arrPrevisto)
                ->color('info')
                ->extraAttributes(['class' => 'bg-gradient-to-r dark:from-sky-900 dark:to-sky-0 from-sky-100 to-sky-0']),

            Stat::make('Aprovado', "R$ {$totalAprovado}")
                ->description('total de orçamento aprovado')
                ->descriptionIcon('heroicon-o-document-currency-dollar')
                ->chart($arrAtual)
                ->color('success')
                ->extraAttributes(['class' => 'bg-gradient-to-r dark:from-green-900 dark:to-green-0 from-green-100 to-green-0']),

            Stat::make('Atual', "R$ {$totalAtual}")
                ->description('total do orçamento dispoível')
                ->descriptionIcon('heroicon-o-banknotes')
                ->chart($arrAprovado)
                ->color('warning')
                ->extraAttributes(['class' => 'bg-gradient-to-r dark:from-orange-900 dark:to-orange-0 from-orange-100 to-orange-0']),
        ];
    }
}
