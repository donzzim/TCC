<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Pages;

use App\Filament\Egap\Externo\Auth\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class BensNoSetor extends ExternoPage
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $cluster = ExternoCluster::class;

    protected static ?string $slug = 'patrimonio/inventario';

    protected static string | \UnitEnum | null $navigationGroup = 'Patrimônio';

    protected static ?string $navigationLabel = 'Bens no Setor (Inventário)';

    protected static ?int $navigationSort = 1;

    protected string $view = 'egap.livewire.support.table-page';

    protected static ?string $title = 'Bens no Setor';

    protected ?string $subheading = 'Selecione os patrimônios para ter acesso as ações';

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
        return \App\Filament\Egap\Externo\Groups\Patrimonio\Components\BensNoSetorTable::class;
    }
}
