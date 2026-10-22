<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Pages;

use App\Filament\Egap\Externo\Auth\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Components\AnaliseSolicitacoesTable;
use App\Models\egap\PlanoContratacao\UsergroupMap;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/**
 * Análise de Solicitações de Plano de Contratação (legado:
 * `pla_telaadministrador.php` + `pla_manipulacaosolicitacao.php`). Aprovar,
 * devolver com comentário, ver histórico e detalhar itens.
 *
 * Restrito aos grupos 38 / 55 do Joomla, como no legado.
 */
class AnaliseSolicitacoes extends ExternoPage
{

    /** Grupos do Joomla autorizados a analisar (legado: `pla_telaadministrador.php`). */
    private const GRUPOS_ANALISE = [38, 55];

    protected static ?string $cluster = ExternoCluster::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Plano de Contratação';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentCheck;

    protected static ?string $slug = 'plano-contratacao/analise';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Análise de Solicitações';

    protected static ?string $navigationLabel = 'Análise de Solicitações';

    protected string $view = 'egap.livewire.support.table-page';

    public static function canAccess(): bool
    {
        return UsergroupMap::usuarioAtualPertence(self::GRUPOS_ANALISE);
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
        return AnaliseSolicitacoesTable::class;
    }
}
