<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\BemIntangivelResource\Widgets;

use App\Filament\Egap\Interno\Groups\Patrimonio\Widgets\BensCountStats;
use App\Models\egap\Patrimonio\BensIntangiveis\BemIntangivel;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Number;

class BensIntangiveisCountStats extends BensCountStats
{
    protected function getStats(): array
    {
        return [
            $this->statCard('Bens intangíveis', Number::format(BemIntangivel::count(), locale: 'pt_BR'))
                ->description('Total de bens cadastrados')
                ->descriptionIcon('heroicon-m-cpu-chip', IconPosition::Before)
                ->color('success')
                ->icon('heroicon-o-cpu-chip'),

            $this->statCard('Valor de aquisição', Number::currency(BemIntangivel::sum('valor_aquisicao'), 'BRL', locale: 'pt_BR'))
                ->description('Somatório do valor de aquisição')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
                ->color('warning')
                ->icon('heroicon-o-banknotes'),

            $this->statCard('Valor líquido contábil', Number::currency(BemIntangivel::sum('valor_liquido_contabil'), 'BRL', locale: 'pt_BR'))
                ->description('Somatório após amortização')
                ->descriptionIcon('heroicon-m-calculator', IconPosition::Before)
                ->color('info')
                ->icon('heroicon-o-calculator'),
        ];
    }
}
