<?php

namespace App\Filament\Egap\Externo\Groups\Agendamento\Pages;

use App\Filament\Egap\Externo\Auth\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use App\Filament\Egap\Externo\Groups\Agendamento\Widgets\ControleTrafegoStats;
use App\Filament\Egap\Interno\Groups\Agendamento\Resources\EquipeResource;
use App\Filament\Egap\Interno\Groups\Agendamento\Resources\FrotaResource;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ControleTrafego extends ExternoPage
{
    protected static ?int $navigationSort = 4;

    protected static ?string $cluster = ExternoCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationCircle;

    protected static string|\UnitEnum|null $navigationGroup = 'Agendamento de Veículos';

    protected static ?string $slug = 'agendamento/controle-trafego';

    protected static ?string $navigationLabel = 'Controle de Tráfego';

    protected static ?string $title = 'Controle de Tráfego';

    protected string $view = 'egap.livewire.support.table-page';

    public function component(): string
    {
        return \App\Filament\Egap\Externo\Groups\Agendamento\Components\ControleTrafegoTable::class;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ControleTrafegoStats::class,
        ];
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('gerenciar_motoristas')
                ->label('Motoristas')
                ->tooltip('Clique para cadastrar motoristas')
                ->icon('heroicon-o-users')
                ->color('gray')
                ->url(fn (): string => EquipeResource::getUrl(panel: 'egap')),

            Action::make('gerenciar_veiculos')
                ->label('Frota')
                ->tooltip('Clique para cadastrar veículos')
                ->icon('heroicon-o-truck')
                ->color('gray')
                ->url(fn (): string => FrotaResource::getUrl(panel: 'egap')),
        ];
    }
}
