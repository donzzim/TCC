<?php

namespace App\Services\Egap;

use App\Filament\Egap\Auth\SetorSwitcherEgap;
use App\Filament\Egap\Externo\Groups\Agendamento\Components\SolicitacaoVeiculoForm;
use App\Filament\Egap\Externo\Groups\Almoxarifado\Components\CarrinhoMateriaisConsumoForm;
use App\Filament\Egap\Externo\Groups\Almoxarifado\Components\MateriaisConsumoTable;
use App\Filament\Egap\Externo\Groups\Almoxarifado\Components\PedidoItensModal;
use App\Filament\Egap\Externo\Groups\Almoxarifado\Components\PedidosAlmoxarifadoTable;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\AtividadeDeCampoEmTransferenciaTable;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\AtividadeDeCampoNaoLocalizadosTable;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\AtividadeDeCampoResumoTable;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\BensNoSetorTable;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\CarrinhoMateriaisPermanentesForm;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\HistoricoDeInventarioOnlineTable;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\LevantamentoComissaoTable;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\MateriaisPermanentesTable;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\MovimentacaoDeMateriaisTable;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\PedidoItensModal as PedidoItensModalPatrimonio;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\PedidosPatrimonioTable;
use App\Filament\Egap\Externo\Pages\Components\AlmoxarifadoCharts;
use App\Filament\Egap\Externo\Pages\Components\PatrimonioCharts;
use App\Filament\Egap\Interno\Groups\Admin\Components\LotacoesTable;
use App\Filament\Egap\Interno\Groups\Admin\Components\RoleUsersTable;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Components\DepreciacaoImovelModal;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Components\ObrasModal;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Components\OcupacoesModal;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Components\ReavaliacoesModal;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Components\TributosModal;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Components\ComissoesModal;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Components\DepreciacaoModal;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Components\EquipesModal;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Components\HistoricoMovimentacoesModal;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Components\MateriaisBaixaModal;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Components\MateriaisTermoModal;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Components\UnidadesModal;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Components\ValidarTermoModal;
use App\Filament\Egap\Interno\Groups\Pedidos\Components\EntregaRecolhimentoTable;
use App\Filament\Egap\Interno\Groups\Pedidos\Components\MateriaisDisponiveisTable;
use App\Filament\Egap\Interno\Groups\Pedidos\Components\MateriaisDoSetorModal;
use App\Filament\Egap\Interno\Groups\Pedidos\Components\PedidosEmAbertoTable;
use App\Filament\Egap\Interno\Groups\Pedidos\Components\ValidarPedidoItensModal;
use App\Filament\Egap\Interno\Groups\Processo\Components\DocumentosModal;
use App\Filament\Egap\Interno\Groups\Processo\Components\EmpenhosModal;
use App\Filament\Egap\Interno\Groups\Processo\Components\MateriaisModal;
use App\Filament\Egap\Interno\Groups\Processo\Components\ReservasModal;
use Livewire\Livewire;

class LivewireComponentsService
{
    public static function getLivewireComponents(): void
    {
        //SESSAO
        Livewire::component('setor-switcher-egap', SetorSwitcherEgap::class);

        //ADMIN
        Livewire::component('adm-egap.user-lotacao', LotacoesTable::class);
        Livewire::component('adm-egap.role-users', RoleUsersTable::class);

        //PATRIMONIO
        Livewire::component('patrimonio.materiais-baixa-modal', MateriaisBaixaModal::class);
        Livewire::component('patrimonio.materiais-termo-modal', MateriaisTermoModal::class);
        Livewire::component('patrimonio.inventario-comissoes-modal', ComissoesModal::class);
        Livewire::component('patrimonio.inventario-equipes-modal', EquipesModal::class);
        Livewire::component('patrimonio.inventario-unidades-modal', UnidadesModal::class);
        Livewire::component('patrimonio.historico-movimentacoes-modal', HistoricoMovimentacoesModal::class);
        Livewire::component('patrimonio.depreciacao-modal', DepreciacaoModal::class);
        Livewire::component('patrimonio.depreciacao-imovel-modal', DepreciacaoImovelModal::class);
        Livewire::component('patrimonio.validar-termo-modal', ValidarTermoModal::class);
        Livewire::component('patrimonio.tributos-modal', TributosModal::class);
        Livewire::component('patrimonio.ocupacoes-modal', OcupacoesModal::class);
        Livewire::component('patrimonio.reavaliacoes-modal', ReavaliacoesModal::class);
        Livewire::component('patrimonio.obras-modal', ObrasModal::class);

        //PEDIDOS
        Livewire::component('pedidos.entrega-recolhimento-table', EntregaRecolhimentoTable::class);
        Livewire::component('pedidos.materiais-do-setor-modal', MateriaisDoSetorModal::class);
        Livewire::component('pedidos.validar-pedido-itens-modal', ValidarPedidoItensModal::class);

        //PROCESSOS
        Livewire::component('processos.documentos-modal', DocumentosModal::class);
        Livewire::component('processos.materiais-modal', MateriaisModal::class);
        Livewire::component('processos.reservas-modal', ReservasModal::class);
        Livewire::component('processos.empenhos-modal', EmpenhosModal::class);

        // PORTAL TRANSPARÊNCIA
        Livewire::component('portal-transparencia.patrimonio-charts', PatrimonioCharts::class);
        Livewire::component('portal-transparencia.almoxarifado-charts', AlmoxarifadoCharts::class);

        //EXTERNO AGENDAMENTO
        Livewire::component('externo-agendamento.solicitacao-veiculo-form', SolicitacaoVeiculoForm::class);

        //EXTERNO ALMOXARIFADO
        Livewire::component('externo-almoxarifado.materiais-disponiveis-table', MateriaisConsumoTable::class);
        Livewire::component('externo-almoxarifado.carrinho-pedido-form', CarrinhoMateriaisConsumoForm::class);
        Livewire::component('externo-almoxarifado.pedidos-almoxarifado-table', PedidosAlmoxarifadoTable::class);
        Livewire::component('externo-almoxarifado.pedido-itens-modal', PedidoItensModal::class);

        //EXTERNO PATRIMONIO
        Livewire::component('externo-patrimonio.bens-no-setor-table', BensNoSetorTable::class);
        Livewire::component('externo-patrimonio.movimentacao-de-materiais-table', MovimentacaoDeMateriaisTable::class);
        Livewire::component('externo-patrimonio.historico-de-inventario-online-table', HistoricoDeInventarioOnlineTable::class);
        Livewire::component('externo-patrimonio.levantamento-comissao-table', LevantamentoComissaoTable::class);
        Livewire::component('externo-patrimonio.atividade-de-campo-resumo-table', AtividadeDeCampoResumoTable::class);
        Livewire::component('externo-patrimonio.atividade-de-campo-nao-localizados-table', AtividadeDeCampoNaoLocalizadosTable::class);
        Livewire::component('externo-patrimonio.atividade-de-campo-em-transferencia-table', AtividadeDeCampoEmTransferenciaTable::class);
        Livewire::component('externo-patrimonio.carrinho-pedido-form', CarrinhoMateriaisPermanentesForm::class);
        Livewire::component('externo-patrimonio.materiais-permanentes-disponiveis-table', MateriaisPermanentesTable::class);
        Livewire::component('externo-patrimonio.pedidos-patrimonio-table', PedidosPatrimonioTable::class);
        Livewire::component('externo-patrimonio.pedido-itens-modal', PedidoItensModalPatrimonio::class);
        Livewire::component('atendimento-pedidos.pedidos-em-aberto-table', PedidosEmAbertoTable::class);
        Livewire::component('atendimento-pedidos.materiais-disponiveis-table', MateriaisDisponiveisTable::class);
    }
}
