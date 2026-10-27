<?php

namespace App\Filament\Egap\Externo\Pages;

use App\Filament\Egap\Externo\Permissions\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use App\Filament\Egap\Externo\Groups\Agendamento\Pages\AgendamentoGerais;
use App\Filament\Egap\Externo\Groups\Agendamento\Pages\AgendamentoUser;
use App\Filament\Egap\Externo\Groups\Agendamento\Pages\ControleTrafego;
use App\Filament\Egap\Externo\Groups\Agendamento\Pages\SolicitacaoVeiculos;
use App\Filament\Egap\Externo\Groups\Almoxarifado\Pages\AcompanhamentoDePedidosAlmoxarifado;
use App\Filament\Egap\Externo\Groups\Almoxarifado\Pages\MateriaisDeConsumo;
use App\Filament\Egap\Externo\Groups\Almoxarifado\Pages\MateriaisDeConsumoDuraveis;
use App\Filament\Egap\Externo\Groups\Patrimonio\Pages\AcompanhamentoDePedidosPatrimonio;
use App\Filament\Egap\Externo\Groups\Patrimonio\Pages\AtividadeDeCampo;
use App\Filament\Egap\Externo\Groups\Patrimonio\Pages\BensNoSetor;
use App\Filament\Egap\Externo\Groups\Patrimonio\Pages\HistoricoDeInventarioOnline;
use App\Filament\Egap\Externo\Groups\Patrimonio\Pages\LevantamentoComissao;
use App\Filament\Egap\Externo\Groups\Patrimonio\Pages\MovimentacaoDeMateriais;
use App\Filament\Egap\Externo\Groups\Patrimonio\Pages\RequisicaoDeMateriais;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Pages\AnaliseSolicitacoes;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Pages\CalendarioContratacoes;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Pages\GraficosPac;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Pages\LimitesPlanoContratacao;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Pages\MeusPlanos;
use App\Filament\Egap\Externo\Groups\PlanoContratacao\Pages\NovaSolicitacao;
use App\Filament\Egap\Externo\Groups\ServicosGerais\Pages\Fiscal;
use App\Filament\Egap\Externo\Groups\ServicosGerais\Pages\Gestor;
use App\Models\egap\Admin\Lotacao;
use App\Models\egap\UserEgap;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class HomePageExterno extends ExternoPage
{
    protected static ?string $cluster = ExternoCluster::class;

    protected static ?int $navigationSort = -1;

    protected static ?string $slug = 'home';

    protected static ?string $navigationLabel = 'Início';

    protected static ?string $title = 'EGAP';

    protected string $view = 'egap.filament.pages.externo.home';

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function usuarioAtual(): ?UserEgap
    {
        return UserEgap::currentAuthenticated();
    }

    public function primeiroNomeUsuario(): string
    {
        $nome = $this->usuarioAtual()?->name;

        return filled($nome) ? (string) str($nome)->before(' ') : 'visitante';
    }

    public function lotacaoAtual(): ?Lotacao
    {
        return $this->usuarioAtual()
            ?->lotacoes()
            ->with(['unidadeJudiciaria', 'setorRef'])
            ->first();
    }

    public function gruposDeAcesso(): array
    {
        $grupos = [
            'Almoxarifado' => [
                [
                    'label' => MateriaisDeConsumo::getNavigationLabel(),
                    'description' => 'Requisitar materiais de consumo do almoxarifado.',
                    'icon' => MateriaisDeConsumo::getNavigationIcon(),
                    'url' => MateriaisDeConsumo::getUrl(),
                ],
                [
                    'label' => MateriaisDeConsumoDuraveis::getNavigationLabel(),
                    'description' => 'Requisitar materiais de consumo duráveis do almoxarifado.',
                    'icon' => MateriaisDeConsumoDuraveis::getNavigationIcon(),
                    'url' => MateriaisDeConsumoDuraveis::getUrl(),
                ],
                [
                    'label' => AcompanhamentoDePedidosAlmoxarifado::getNavigationLabel(),
                    'description' => 'Consultar o andamento dos pedidos de materiais de consumo.',
                    'icon' => AcompanhamentoDePedidosAlmoxarifado::getNavigationIcon(),
                    'url' => AcompanhamentoDePedidosAlmoxarifado::getUrl(),
                ],
            ],
            'Patrimônio' => [
                [
                    'label' => BensNoSetor::getNavigationLabel(),
                    'description' => 'Consultar e conferir os bens patrimoniais do setor.',
                    'icon' => BensNoSetor::getNavigationIcon(),
                    'url' => BensNoSetor::getUrl(),
                ],
                [
                    'label' => MovimentacaoDeMateriais::getNavigationLabel(),
                    'description' => 'Solicitar a movimentação de bens entre setores.',
                    'icon' => MovimentacaoDeMateriais::getNavigationIcon(),
                    'url' => MovimentacaoDeMateriais::getUrl(),
                ],
                [
                    'label' => RequisicaoDeMateriais::getNavigationLabel(),
                    'description' => 'Requisitar materiais permanentes à Seção de Patrimônio.',
                    'icon' => RequisicaoDeMateriais::getNavigationIcon(),
                    'url' => RequisicaoDeMateriais::getUrl(),
                ],
                [
                    'label' => AcompanhamentoDePedidosPatrimonio::getNavigationLabel(),
                    'description' => 'Consultar o andamento dos pedidos de materiais permanentes.',
                    'icon' => AcompanhamentoDePedidosPatrimonio::getNavigationIcon(),
                    'url' => AcompanhamentoDePedidosPatrimonio::getUrl(),
                ],
                [
                    'label' => HistoricoDeInventarioOnline::getNavigationLabel(),
                    'description' => 'Consultar os inventários já realizados no setor.',
                    'icon' => HistoricoDeInventarioOnline::getNavigationIcon(),
                    'url' => HistoricoDeInventarioOnline::getUrl(),
                ],
                [
                    'label' => LevantamentoComissao::getNavigationLabel(),
                    'description' => 'Registrar o levantamento físico da Comissão de Inventário.',
                    'icon' => LevantamentoComissao::getNavigationIcon(),
                    'url' => LevantamentoComissao::getUrl(),
                ],
                [
                    'label' => AtividadeDeCampo::getNavigationLabel(),
                    'description' => 'Acompanhar o progresso da conferência de inventário por setor.',
                    'icon' => AtividadeDeCampo::getNavigationIcon(),
                    'url' => AtividadeDeCampo::getUrl(),
                ],
            ],
            'Agendamento de Veículos' => [
                [
                    'label' => SolicitacaoVeiculos::getNavigationLabel(),
                    'description' => 'Solicitar veículo para deslocamento oficial.',
                    'icon' => SolicitacaoVeiculos::getNavigationIcon(),
                    'url' => SolicitacaoVeiculos::getUrl(),
                ],
                [
                    'label' => AgendamentoUser::getNavigationLabel(),
                    'description' => 'Visualizar agendamentos feitos por voce.',
                    'icon' => AgendamentoUser::getNavigationIcon(),
                    'url' => AgendamentoUser::getUrl(),
                ],
                [
                    'label' => AgendamentoGerais::getNavigationLabel(),
                    'description' => 'Acompanhar agendamentos feitos no Setor.',
                    'icon' => AgendamentoGerais::getNavigationIcon(),
                    'url' => AgendamentoGerais::getUrl(),
                ],
                [
                    'label' => ControleTrafego::getNavigationLabel(),
                    'description' => 'Solicitar veículo para deslocamento oficial.',
                    'icon' => ControleTrafego::getNavigationIcon(),
                    'url' => ControleTrafego::getUrl(),
                ],
            ],
            'Plano de Contratação' => [
                [
                    'label' => NovaSolicitacao::getNavigationLabel(),
                    'description' => 'Criar uma nova solicitação de plano de contratação.',
                    'icon' => NovaSolicitacao::getNavigationIcon(),
                    'url' => NovaSolicitacao::getUrl(),
                ],
                [
                    'label' => MeusPlanos::getNavigationLabel(),
                    'description' => 'Consultar e acompanhar os planos de contratação do setor.',
                    'icon' => MeusPlanos::getNavigationIcon(),
                    'url' => MeusPlanos::getUrl(),
                ],
                [
                    'label' => CalendarioContratacoes::getNavigationLabel(),
                    'description' => 'Visualizar o calendário de contratações.',
                    'icon' => CalendarioContratacoes::getNavigationIcon(),
                    'url' => CalendarioContratacoes::getUrl(),
                ],
                [
                    'label' => GraficosPac::getNavigationLabel(),
                    'description' => 'Visualizar gráficos do Plano Anual de Contratações.',
                    'icon' => GraficosPac::getNavigationIcon(),
                    'url' => GraficosPac::getUrl(),
                ],
                [
                    'label' => AnaliseSolicitacoes::getNavigationLabel(),
                    'description' => 'Analisar as solicitações de plano de contratação.',
                    'icon' => AnaliseSolicitacoes::getNavigationIcon(),
                    'url' => AnaliseSolicitacoes::getUrl(),
                    'can' => AnaliseSolicitacoes::canAccess(),
                ],
                [
                    'label' => LimitesPlanoContratacao::getNavigationLabel(),
                    'description' => 'Consultar os limites do plano de contratação.',
                    'icon' => LimitesPlanoContratacao::getNavigationIcon(),
                    'url' => LimitesPlanoContratacao::getUrl(),
                    'can' => LimitesPlanoContratacao::canAccess(),
                ],
            ],
            'Serviços Gerais' => [
                [
                    'label' => Gestor::getNavigationLabel(),
                    'description' => 'Gerenciar contratos, formulários e fiscais dos serviços gerais.',
                    'icon' => Gestor::getNavigationIcon(),
                    'url' => Gestor::getUrl(),
                ],
                [
                    'label' => Fiscal::getNavigationLabel(),
                    'description' => 'Preencher os formulários de fiscalização das localidades atendidas.',
                    'icon' => Fiscal::getNavigationIcon(),
                    'url' => Fiscal::getUrl(),
                ],
            ],
            'Portal Transparência' => [
                [
                    'label' => PortalTransparencia::getNavigationLabel(),
                    'description' => 'Visualizar gráficos correspondentes aos dados de Patrimônio e Almoxarifado.',
                    'icon' => PortalTransparencia::getNavigationIcon() ?? 'heroicon-o-presentation-chart-bar',
                    'url' => PortalTransparencia::getUrl(),
                ],
            ],
        ];

        return collect($grupos)
            ->map(fn (array $links): array => array_values(
                array_filter($links, fn (array $link): bool => $link['can'] ?? true)
            ))
            ->filter(fn (array $links): bool => filled($links))
            ->all();
    }
}
