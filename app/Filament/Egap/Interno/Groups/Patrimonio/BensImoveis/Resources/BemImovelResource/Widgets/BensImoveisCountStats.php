<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\BemImovelResource\Widgets;

use App\Filament\Egap\Interno\Groups\Patrimonio\Widgets\BensCountStats;
use App\Models\egap\Patrimonio\BensImoveis\BemImovel;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Number;

class BensImoveisCountStats extends BensCountStats
{
    protected function getStats(): array
    {
        return [
            $this->statCard('Bens imóveis', Number::format(BemImovel::count(), locale: 'pt_BR'))
                ->description('Total de imóveis cadastrados')
                ->descriptionIcon('heroicon-m-home-modern', IconPosition::Before)
                ->color('success')
                ->icon('heroicon-o-building-office-2'),

            $this->statCard('Valor reavaliado', Number::currency(BemImovel::sum('valor_reavaliado'), 'BRL', locale: 'pt_BR'))
                ->description('Somatório do valor reavaliado')
                ->descriptionIcon('heroicon-m-arrow-trending-up', IconPosition::Before)
                ->color('warning')
                ->icon('heroicon-o-banknotes'),

            $this->statCard('Valor líquido contábil', Number::currency(BemImovel::sum('valor_liquido_contabil'), 'BRL', locale: 'pt_BR'))
                ->description('Somatório após depreciação')
                ->descriptionIcon('heroicon-m-calculator', IconPosition::Before)
                ->color('info')
                ->icon('heroicon-o-calculator'),
        ];
    }
}
