<?php

namespace App\Filament\Egap\Externo\Groups\ServicosGerais\Pages;

use App\Filament\Egap\Externo\Permissions\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use App\Filament\Egap\Externo\Groups\ServicosGerais\Components\PeriodosTable;
use App\Filament\Egap\Externo\Groups\ServicosGerais\Concerns\SelecionaContratoAtual;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Painel do gestor de contrato (legado: avagestor.php).
 *
 * O usuário vem da sessão ({@see \App\Filament\Egap\Auth\EgapSession}),
 * resolvido pelo CPF de quem está logado; antes havia um modal que pedia a
 * identificação à mão. O contrato continua sendo escolhido por
 * {@see SelecionaContratoAtual::resolverContratoAtual()}, porque ali a escolha
 * é real — o gestor pode gerir mais de um contrato (legado: seletor de
 * contrato + POST + $_SESSION["csg_contrato_selecionado"]).
 *
 * O corpo da tela (períodos, criar formulários, aplicar tácito, pendentes,
 * para análise, localidades, fiscais) vem de {@see PeriodosTable}.
 */
class Gestor extends ExternoPage
{
    use SelecionaContratoAtual;

    protected static ?string $cluster = ExternoCluster::class;
    protected static string|null|\UnitEnum $navigationGroup = 'Serviços Gerais';
    protected static string | BackedEnum | null $navigationIcon = Heroicon::UserGroup;
    protected static ?string $slug = 'servicos-gerais/gestores';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Gestores';
    protected static ?string $navigationLabel = 'Gestores';
    protected string $view = 'egap.livewire.support.table-page';

    public function mount(): void
    {
        $this->resolverContratoAtual();
    }

    public function getHeaderActions(): array
    {
        return [
            $this->selecionarContratoHeaderAction(),
        ];
    }

    public function component(): string
    {
        return PeriodosTable::class;
    }
}
