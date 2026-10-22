<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Pages;

use App\Filament\Egap\Externo\Auth\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Components\NovaSolicitacaoForm;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/**
 * Nova Solicitação de Plano de Contratação (legado: `pla_novasolicitacao.php`).
 *
 * Página "casca": o formulário (cabeçalho + itens de bens/serviços + salvar
 * rascunho / enviar) fica em
 * {@see NovaSolicitacaoForm}.
 *
 * Usuário e setor vêm da sessão ({@see \App\Filament\Egap\Auth\EgapSession}),
 * como no resto do Ambiente Externo.
 */
class NovaSolicitacao extends ExternoPage
{
    protected static ?string $cluster = ExternoCluster::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Plano de Contratação';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentPlus;

    protected static ?string $slug = 'plano-contratacao/nova-solicitacao';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Nova Solicitação';

    protected static ?string $navigationLabel = 'Nova Solicitação';

    protected string $view = 'egap.filament.pages.externo.plano-contratacao.nova-solicitacao';

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

}
