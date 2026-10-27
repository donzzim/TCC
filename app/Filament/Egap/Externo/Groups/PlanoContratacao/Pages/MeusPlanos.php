<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Pages;

use App\Filament\Egap\Externo\Permissions\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Components\MeusPlanosTable;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/**
 * "Meus Planos" — acompanhamento e edição dos planos do setor do usuário
 * (legado: `pla_planodecontratacao.php`). O corpo é a tabela Livewire
 * {@see MeusPlanosTable}.
 */
class MeusPlanos extends ExternoPage
{
    protected static ?string $cluster = ExternoCluster::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Plano de Contratação';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static ?string $slug = 'plano-contratacao/meus-planos';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Meus Planos';

    protected static ?string $navigationLabel = 'Meus Planos';

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
        return MeusPlanosTable::class;
    }
}
