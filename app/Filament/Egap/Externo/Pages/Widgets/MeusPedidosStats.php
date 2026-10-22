<?php

namespace App\Filament\Egap\Externo\Pages\Widgets;

use App\Filament\Egap\Externo\Groups\Agendamento\Components\SolicitacaoVeiculoForm;
use App\Filament\Egap\Externo\Groups\Agendamento\Pages\SolicitacaoVeiculos;
use App\Filament\Egap\Externo\Groups\Almoxarifado\Components\PedidosAlmoxarifadoTable;
use App\Filament\Egap\Externo\Groups\Almoxarifado\Pages\AcompanhamentoDePedidosAlmoxarifado;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\PedidosPatrimonioTable;
use App\Filament\Egap\Externo\Groups\Patrimonio\Pages\AcompanhamentoDePedidosPatrimonio;
use App\Models\egap\Agendamento\Solicitacao;
use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\UserEgap;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Resumo, para o usuário autenticado do Ambiente Externo, dos pedidos e
 * solicitações que ele mesmo abriu e que ainda estão em análise — mesmos
 * agrupamentos de situação usados em
 * {@see PedidosAlmoxarifadoTable},
 * {@see PedidosPatrimonioTable} e
 * {@see SolicitacaoVeiculoForm}.
 */
class MeusPedidosStats extends StatsOverviewWidget
{
    protected const SETOR_ALMOXARIFADO = 799;

    protected const SETOR_PATRIMONIO = 1239;

    protected const SITUACOES_PENDENTES_ALMOXARIFADO = [6];

    protected const SITUACOES_PENDENTES_PATRIMONIO = [6, 8, 9, 10];

    protected const TIPO_AGENDAMENTO_VEICULOS = 1;

    protected const SITUACAO_EM_ANALISE_VEICULO = 6;

    protected int|array|null $columns = 3;

    protected function getStats(): array
    {
        $usuario = UserEgap::currentAuthenticated();

        return [
            $this->statCard(
                label: 'Pedidos de Consumo em análise',
                valor: $this->contarPedidos(self::SETOR_ALMOXARIFADO, $usuario, self::SITUACOES_PENDENTES_ALMOXARIFADO),
                icon: 'heroicon-o-inbox',
                url: AcompanhamentoDePedidosAlmoxarifado::getUrl(),
            ),

            $this->statCard(
                label: 'Pedidos de Patrimônio em análise',
                valor: $this->contarPedidos(self::SETOR_PATRIMONIO, $usuario, self::SITUACOES_PENDENTES_PATRIMONIO),
                icon: 'heroicon-o-cube',
                url: AcompanhamentoDePedidosPatrimonio::getUrl(),
            ),

            $this->statCard(
                label: 'Solicitações de Veículo em análise',
                valor: $this->contarSolicitacoesVeiculo($usuario),
                icon: 'heroicon-o-truck',
                url: SolicitacaoVeiculos::getUrl(),
            ),
        ];
    }

    private function statCard(string $label, int $valor, string $icon, string $url): Stat
    {
        return Stat::make($label, (string) $valor)
            ->description($valor > 0 ? 'Aguardando atendimento' : 'Nenhum pedido pendente')
            ->icon($icon)
            ->color($valor > 0 ? 'warning' : 'success')
            ->url($url)
            ->extraAttributes([
                'class' => 'ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200',
            ]);
    }

    /**
     * @param  array<int>  $situacoes
     */
    private function contarPedidos(int $setorResponsavel, ?UserEgap $usuario, array $situacoes): int
    {
        if (! $usuario) {
            return 0;
        }

        return Pedidos::query()
            ->where(fn(Builder $query) => $this->filtrarSetorResponsavel($query, $setorResponsavel))
            ->where('Solicitante', $usuario->id)
            ->whereIn('idSituacao', $situacoes)
            ->count();
    }

    private function filtrarSetorResponsavel(Builder $query, int $setorResponsavel): void
    {
        $query->where('setor_responsavel', $setorResponsavel);

        if ($setorResponsavel === self::SETOR_PATRIMONIO) {
            $query->orWhereNull('setor_responsavel');
        }
    }

    private function contarSolicitacoesVeiculo(?UserEgap $usuario): int
    {
        if (! $usuario) {
            return 0;
        }

        return Solicitacao::query()
            ->where('tipo', self::TIPO_AGENDAMENTO_VEICULOS)
            ->where('id_solicitante', $usuario->id)
            ->where('id_situacao', self::SITUACAO_EM_ANALISE_VEICULO)
            ->count();
    }
}
