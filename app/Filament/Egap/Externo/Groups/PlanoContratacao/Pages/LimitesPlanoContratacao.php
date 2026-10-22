<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Pages;

use App\Filament\Egap\Externo\Auth\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Components\LimitesTable;
use App\Models\egap\PlanoContratacao\UsergroupMap;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/**
 * Limites anuais de gasto do PCA por setor (legado: `pla_limite.php`).
 * Restrito ao grupo 52 do Joomla, como no legado.
 */
class LimitesPlanoContratacao extends ExternoPage
{
    /** Grupo do Joomla autorizado a gerir limites (legado: `pla_limite.php`). */
    private const GRUPO_LIMITES = 52;

    protected static ?string $cluster = ExternoCluster::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Plano de Contratação';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Scale;

    protected static ?string $slug = 'plano-contratacao/limites';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Limites de Plano de Contratação';

    protected static ?string $navigationLabel = 'Limites';

    protected string $view = 'egap.livewire.support.table-page';

    public static function canAccess(): bool
    {
        return UsergroupMap::usuarioAtualPertence([self::GRUPO_LIMITES]);
    }

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
        return LimitesTable::class;
    }
}
