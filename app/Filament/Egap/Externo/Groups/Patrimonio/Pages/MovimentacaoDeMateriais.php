<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Pages;

use App\Filament\Egap\Externo\Permissions\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class MovimentacaoDeMateriais extends ExternoPage
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $cluster = ExternoCluster::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Patrimônio';

    protected static ?string $slug = 'patrimonio/movimentacao-de-materiais';

    protected static ?string $navigationLabel = 'Movimentação de Materiais';

    protected static ?string $title = 'Movimentação de Materiais';

    protected static ?int $navigationSort = 2;

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
        return \App\Filament\Egap\Externo\Groups\Patrimonio\Components\MovimentacaoDeMateriaisTable::class;
    }
}
