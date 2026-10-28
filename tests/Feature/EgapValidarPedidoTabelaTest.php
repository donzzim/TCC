<?php

namespace Tests\Feature;

use App\Filament\Egap\Interno\Groups\Pedidos\Resources\ValidarPedidoResource\Pages\ListValidarPedidos;
use App\Models\egap\Almoxarifado\ItemPedido;
use App\Models\egap\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class EgapValidarPedidoTabelaTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['emes', 'egap'];

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

    public function test_item_de_pedido_sem_solicitante_aparece_como_nao_informado(): void
    {
        $item = ItemPedido::query()
            ->whereHas('idPedidoRef', fn ($q) => $q->whereNull('Solicitante'))
            ->latest('idPedido')
            ->first();

        if (! $item) {
            $this->markTestSkipped('nenhum pedido sem solicitante neste banco');
        }

        $this->assertSame('Solicitante: não informado', $this->descricaoDoPedido($item));
    }

    public function test_item_sem_pedido_nao_quebra_a_tabela(): void
    {
        $item = ItemPedido::query()->whereDoesntHave('idPedidoRef')->first();

        if (! $item) {
            $this->markTestSkipped('nenhum item sem pedido neste banco');
        }

        $this->assertSame('Solicitante: não informado', $this->descricaoDoPedido($item));

        Livewire::test(ListValidarPedidos::class)
            ->assertTableActionHidden('materiais_no_setor', $item);
    }

    /** A busca da tabela varre relacionamentos de 200 mil itens; a coluna é testada direto. */
    private function descricaoDoPedido(ItemPedido $item): string
    {
        $tabela = Livewire::test(ListValidarPedidos::class)->assertOk()->instance()->getTable();

        return (string) $tabela->getColumn('idPedido')->record($item)->getDescriptionBelow();
    }
}
