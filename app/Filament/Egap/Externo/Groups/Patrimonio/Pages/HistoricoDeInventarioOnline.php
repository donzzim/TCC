<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Pages;

use App\Filament\Egap\Externo\Auth\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class HistoricoDeInventarioOnline extends ExternoPage
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';
    protected static ?string $cluster = ExternoCluster::class;
    protected static string | \UnitEnum | null $navigationGroup = 'Patrimônio';
    protected static ?string $navigationLabel = 'Histórico de Inventário Online';
    protected static ?string $title = 'Histórico de Inventário Online';
    protected static ?string $slug = 'patrimonio/inventario-historico';
    protected static ?int $navigationSort = 5;
    protected string $view = 'egap.livewire.support.table-page';

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function component(): string
    {
        return \App\Filament\Egap\Externo\Groups\Patrimonio\Components\HistoricoDeInventarioOnlineTable::class;
    }
}
