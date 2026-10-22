<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

abstract class BensCountStats extends StatsOverviewWidget
{
    protected int|array|null $columns = 3;

    protected function statCard(string $label, string $value): Stat
    {
        return Stat::make($label, $value)
            ->extraAttributes([
                'class' => 'ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200',
            ]);
    }
}
