<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources\SetoresResource\Widgets;

use App\Models\egap\Cadastro\Setores;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SetoresCountStats extends StatsOverviewWidget
{
    protected int | array | null $columns = 2;
    protected function getStats(): array
    {
        return [
            Stat::make('Comarcas', fn() => Setores::whereColumn('id', '=', 'codigodauo')->count())
                ->description('Total de comarcas cadastradas')
                ->descriptionIcon('heroicon-m-map-pin', IconPosition::Before)
                ->color('info')
                ->icon('heroicon-o-building-office-2')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3])
                ->extraAttributes([
                    'class' => 'ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200',
                ]),

            Stat::make('Setores', fn() => Setores::whereColumn('id', '!=', 'codigodauo')->count())
                ->description('Total de setores cadastrados')
                ->descriptionIcon('heroicon-m-arrow-trending-up', IconPosition::Before)
                ->color('success')
                ->icon('heroicon-o-squares-2x2')
                ->chart([3, 5, 4, 6, 4, 7, 6, 8])
                ->extraAttributes([
                    'class' => 'ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200',
                ]),
        ];
    }
}
