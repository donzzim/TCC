<?php

namespace App\Filament\Egap\Externo\Groups\Agendamento\Pages;

use App\Filament\Egap\Externo\Auth\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use Filament\Pages\Page;

class SolicitacaoVeiculos extends ExternoPage
{
    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = ExternoCluster::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-truck';

    protected static string | \UnitEnum | null $navigationGroup = 'Agendamento de Veículos';

    protected static ?string $slug = 'agendamento/solicitacao-veiculos';

    protected static ?string $navigationLabel = 'Solicitação';

    protected static ?string $title = 'Solicitação de Veículo';

    protected string $view = 'egap.livewire.support.table-page';

    public function component(): string
    {
        return \App\Filament\Egap\Externo\Groups\Agendamento\Components\SolicitacaoVeiculoForm::class;
    }
}
