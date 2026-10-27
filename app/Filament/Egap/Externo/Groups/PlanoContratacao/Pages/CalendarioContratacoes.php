<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Pages;

use App\Filament\Egap\Externo\Permissions\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Widgets\ContratacoesCalendarWidget;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/**
 * Calendário de Contratações (legado: `pla_calendario.php`). O corpo é o widget
 * {@see ContratacoesCalendarWidget}.
 */
class CalendarioContratacoes extends ExternoPage
{
    protected static ?string $cluster = ExternoCluster::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Plano de Contratação';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    protected static ?string $slug = 'plano-contratacao/calendario';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Calendário de Contratações';

    protected static ?string $navigationLabel = 'Calendário';

    protected string $view = 'egap.filament.pages.externo.plano-contratacao.calendario-contratacoes';

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

}
