<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Pages;

use App\Filament\Egap\Externo\Auth\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

/**
 * Painel de gestão do inventário online (legado: atividades.php +
 * api/atividades.api.php), usado pela Comissão de Inventário para acompanhar o
 * andamento da conferência em todos os setores: progresso por Unidade/Setor/
 * Complemento, bens não localizados e bens em transferência pendente.
 *
 * Diferente das demais páginas do cluster, não é restrito ao setor do usuário
 * (SetorSelecionado): reflete a visão multi-setor da Comissão.
 *
 * Não replicado do legado: o envio de e-mail aos setores (fora do escopo desta
 * tela) e a reabertura de atividade por setor (endpoint
 * bensainventariar_operacoes.api.php::reabrir não fornecido como referência).
 */
class AtividadeDeCampo extends ExternoPage
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $cluster = ExternoCluster::class;
    protected static ?string $slug = 'patrimonio/atividade-de-campo';
    protected static string | \UnitEnum | null $navigationGroup = 'Patrimônio';
    protected static ?string $navigationLabel = 'Ativididade de Campo';
    protected static ?string $title = 'Ativididade de Campo';
    protected static ?int $navigationSort = 6;
    protected string $view = 'egap.filament.pages.externo.patrimonio.atividade-de-campo';

    public ?int $inventarioId = null;

    public string $secao = 'resumo';

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function updatedInventarioId(): void
    {
        $this->dispatch('inventario-selecionado', inventario: $this->inventarioId);
    }

    public function selecionarSecao(string $secao): void
    {
        $this->secao = $secao;
    }

    public function inventarios(): array
    {
        return Inventario::query()
            ->orderByDesc('ano_inventario')
            ->orderByDesc('num_inventario')
            ->get()
            ->mapWithKeys(fn(Inventario $inventario): array => [
                $inventario->id => "{$inventario->num_inventario}/{$inventario->ano_inventario}",
            ])
            ->toArray();
    }
}
