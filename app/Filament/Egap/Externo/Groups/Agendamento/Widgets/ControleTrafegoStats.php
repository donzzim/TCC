<?php

namespace App\Filament\Egap\Externo\Groups\Agendamento\Widgets;

use App\Models\egap\Agendamento\Solicitacao;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ControleTrafegoStats extends StatsOverviewWidget
{
    protected int|array|null $columns = 3;

    protected function getStats(): array
    {
        return [
            Stat::make('Em Análise', (string) $this->contar(Solicitacao::SITUACAO_EM_ANALISE))
                ->icon('heroicon-o-inbox')
                ->chart([1, 1, 1, 1, 1, 1, 1])
                ->color('warning'),

            Stat::make('Agendados', (string) $this->contar(Solicitacao::SITUACAO_AGENDADO))
                ->icon('heroicon-o-calendar-days')
                ->chart([1, 1, 1, 1, 1, 1, 1])
                ->color('info'),

            Stat::make('Concluídos', (string) $this->contar(Solicitacao::SITUACAO_CONCLUIDO))
                ->icon('heroicon-o-check-circle')
                ->chart([1, 1, 1, 1, 1, 1, 1])
                ->color('success'),
        ];
    }

    private function contar(int $situacao): int
    {
        return Solicitacao::query()
            ->where('tipo', Solicitacao::TIPO_VEICULOS)
            ->where('id_situacao', $situacao)
            ->count();
    }
}
