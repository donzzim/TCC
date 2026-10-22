<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\BemMovelResource\Widgets;

use App\Filament\Egap\Interno\Groups\Patrimonio\Widgets\BensCountStats;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Number;

class BensMoveisCountStats extends BensCountStats
{
    // Situações operacionais consideradas "ativas" (Ativo, Baixa em andamento, Cadastrado Manualmente),
    // mesmo critério usado em App\Services\PatrimonioDashboardService.
    private const SITUACOES_ATIVAS = [1, 7, 8];

    protected function getStats(): array
    {
        return [
            $this->statCard('Bens móveis', Number::format(BemMovel::count(), locale: 'pt_BR'))
                ->description('Total de bens cadastrados')
                ->descriptionIcon('heroicon-m-cube', IconPosition::Before)
                ->color('success')
                ->icon('heroicon-o-cube'),

            $this->statCard('Bens ativos', Number::format(
                BemMovel::whereIn('SituacaoBem', self::SITUACOES_ATIVAS)->count(),
                locale: 'pt_BR'
            ))
                ->description('Situações operacionais ativas')
                ->descriptionIcon('heroicon-m-check-badge', IconPosition::Before)
                ->color('warning')
                ->icon('heroicon-o-check-badge'),

            $this->statCard('Valor de aquisição', Number::currency(BemMovel::sum('ValorAquisicao'), 'BRL', locale: 'pt_BR'))
                ->description('Somatório do valor de aquisição')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
                ->color('info')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
