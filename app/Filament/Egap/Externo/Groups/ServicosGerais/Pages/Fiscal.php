<?php

namespace App\Filament\Egap\Externo\Groups\ServicosGerais\Pages;

use App\Filament\Egap\Externo\Permissions\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use App\Filament\Egap\Externo\Groups\ServicosGerais\Components\FormulariosFiscalTable;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Painel do fiscal (legado: avafiscal.php).
 *
 * O usuário logado vem da sessão ({@see \App\Filament\Egap\Auth\EgapSession}),
 * resolvido pelo CPF — é a partir dele que {@see FormulariosFiscalTable}
 * resolve quais localidades o fiscal atende.
 */
class Fiscal extends ExternoPage
{

    protected static ?string $cluster = ExternoCluster::class;
    protected static string|null|\UnitEnum $navigationGroup = 'Serviços Gerais';
    protected static string | BackedEnum | null $navigationIcon = Heroicon::ShieldCheck;
    protected static ?string $slug = 'servicos-gerais/fiscais';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Fiscais';
    protected static ?string $navigationLabel = 'Fiscais';
    protected string $view = 'egap.livewire.support.table-page';

    public function component(): string
    {
        return FormulariosFiscalTable::class;
    }
}
