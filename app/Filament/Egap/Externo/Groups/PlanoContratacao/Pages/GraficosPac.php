<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Pages;

use App\Filament\Egap\Externo\Auth\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Widgets\MaioresContratosChart;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Widgets\NovoVsRenovacaoChart;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Widgets\RankingSetoresChart;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/**
 * Painel de gráficos do PAC (legado: `pla_graficospac.php`). Cada gráfico
 * ({@see NovoVsRenovacaoChart},
 * {@see RankingSetoresChart},
 * {@see MaioresContratosChart})
 * tem o próprio seletor de ano.
 */
class GraficosPac extends ExternoPage
{
    protected static ?string $cluster = ExternoCluster::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Plano de Contratação';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static ?string $slug = 'plano-contratacao/graficos-pac';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Gráficos PAC';

    protected static ?string $navigationLabel = 'Gráficos PAC';

    protected string $view = 'egap.filament.pages.externo.plano-contratacao.graficos-pac';

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
