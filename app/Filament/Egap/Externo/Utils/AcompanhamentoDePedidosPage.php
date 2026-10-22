<?php

namespace App\Filament\Egap\Externo\Utils;

use App\Filament\Egap\Externo\Auth\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

abstract class AcompanhamentoDePedidosPage extends ExternoPage
{
    protected static ?string $cluster = ExternoCluster::class;

    protected string $view = 'egap.livewire.support.table-page';

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    abstract public function component(): string;
}
