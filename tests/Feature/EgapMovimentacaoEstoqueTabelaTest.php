<?php

namespace Tests\Feature;

use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\MovimentacaoEstoqueResource\Pages\ListMovimentacaoEstoques;
use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use App\Models\egap\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class EgapMovimentacaoEstoqueTabelaTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['emes'];

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::query()->get()->first(fn (User $u): bool => $u->isAdminEgap());

        if (! $admin) {
            $this->markTestSkipped('nenhum administrador do EGAP neste banco');
        }

        $this->actingAs($admin, User::GUARD);
        Filament::setCurrentPanel('egap');
    }

    public function test_coluna_pedido_mostra_o_id_do_pedido_da_movimentacao(): void
    {
        $movimentacao = MovimentacaoEstoque::query()->whereHas('idPedidoRef')->latest('id')->first();

        if (! $movimentacao) {
            $this->markTestSkipped('nenhuma movimentação com pedido neste banco');
        }

        $tabela = Livewire::test(ListMovimentacaoEstoques::class)->assertOk()->instance()->getTable();

        $this->assertSame(
            (string) $movimentacao->id_pedido,
            (string) $tabela->getColumn('idPedidoRef.id')->record($movimentacao)->getState(),
        );
    }

    public function test_busca_pelo_numero_do_pedido_nao_quebra_e_encontra_a_movimentacao(): void
    {
        $movimentacao = MovimentacaoEstoque::query()->whereHas('idPedidoRef')->latest('id')->first();

        if (! $movimentacao) {
            $this->markTestSkipped('nenhuma movimentação com pedido neste banco');
        }

        Livewire::test(ListMovimentacaoEstoques::class)
            ->searchTable((string) $movimentacao->id_pedido)
            ->assertOk()
            ->assertCanSeeTableRecords([$movimentacao]);
    }

    public function test_busca_por_texto_nao_quebra(): void
    {
        Livewire::test(ListMovimentacaoEstoques::class)
            ->searchTable('cadeira')
            ->assertOk();
    }

    public function test_busca_por_coluna_de_relacao_encontra_o_mesmo_que_where_has(): void
    {
        $termo = 'cadeira';
        $esperado = MovimentacaoEstoque::query()
            ->whereHas('materialRel', fn ($q) => $q->where('descricao_detalhada', 'like', "%{$termo}%"))
            ->orWhereHas('notaFiscal', fn ($q) => $q->where('num_documento', 'like', "%{$termo}%"))
            ->orWhereHas('tipoMovimentacaoRel', fn ($q) => $q->where('descricao', 'like', "%{$termo}%"))
            ->orWhereHas('setor', fn ($q) => $q->where('setor', 'like', "%{$termo}%")->orWhere('unidadeorganizacional', 'like', "%{$termo}%"))
            ->orWhereHas('atualizadoPor', fn ($q) => $q->where('name', 'like', "%{$termo}%"))
            ->count();

        $tabela = Livewire::test(ListMovimentacaoEstoques::class)
            ->searchTable($termo)
            ->assertOk()
            ->instance();

        $this->assertSame($esperado, $tabela->getTableRecords()->total());
    }
}
