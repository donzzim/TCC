<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Pages;

use App\Filament\Egap\Externo\Permissions\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class CarregarDados extends ExternoPage
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $cluster = ExternoCluster::class;
    protected static string | \UnitEnum | null $navigationGroup = 'Patrimônio';
    protected static ?string $navigationLabel = 'Carregar dados';
    protected static ?string $slug = 'patrimonio/carregar-dados';
    protected static ?int $navigationSort = 7;
    protected string $view = 'egap.filament.pages.externo.patrimonio.carregar-dados';

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
