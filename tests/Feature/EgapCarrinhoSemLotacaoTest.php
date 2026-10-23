<?php

namespace Tests\Feature;

use App\Filament\Egap\Auth\EgapSession;
use App\Filament\Egap\Externo\Groups\Almoxarifado\Components\CarrinhoMateriaisConsumoForm;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\CarrinhoMateriaisPermanentesForm;
use App\Filament\Egap\Externo\Utils\Carrinho;
use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sem lotação utilizável o pedido não tem destino: o envio é bloqueado e o
 * usuário é orientado a procurar a administração, que é quem define lotações.
 */
class EgapCarrinhoSemLotacaoTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['emes', 'egap'];

    private function autenticarSemLotacao(): User
    {
        $user = User::query()->whereDoesntHave('setores')->first();

        if (! $user) {
            $this->markTestSkipped('nenhum usuário sem lotação no banco');
        }

        $this->actingAs($user, User::GUARD);
        $this->assertFalse(EgapSession::lotacaoUtilizavelComoDestino());

        return $user;
    }

    public function test_the_form_warns_that_only_the_administration_sets_the_lotacao(): void
    {
        $this->autenticarSemLotacao();

        Livewire::test(CarrinhoMateriaisConsumoForm::class)
            ->assertOk()
            ->assertSee(Carrinho::AVISO_SEM_LOTACAO);
    }

    public function test_consumo_is_blocked_without_lotacao(): void
    {
        $this->autenticarSemLotacao();
        $antes = Pedidos::query()->count();

        Livewire::test(CarrinhoMateriaisConsumoForm::class)
            ->set('carrinho', [[
                'material_id' => 1,
                'descricao_resumida_id' => 1,
                'descricao' => 'Material de teste',
                'quantidade' => 1,
                'preco_unitario' => 1.0,
            ]])
            ->set('data.justificativa', 'Teste automatizado')
            ->call('enviarPedido')
            ->assertNotified(Carrinho::AVISO_SEM_LOTACAO);

        $this->assertSame($antes, Pedidos::query()->count());
    }

    public function test_permanente_is_blocked_without_lotacao(): void
    {
        $this->autenticarSemLotacao();
        $antes = Pedidos::query()->count();

        Livewire::test(CarrinhoMateriaisPermanentesForm::class)
            ->set('carrinho', [[
                'material_id' => 1,
                'descricao' => 'Material de teste',
                'quantidade' => 1,
                'tipo_atendimento' => 'adicao',
                'patrimonio_substituido' => null,
                'justificativa' => 'Teste automatizado',
                'preco_unitario' => 1.0,
            ]])
            ->call('enviarPedido')
            ->assertNotified(Carrinho::AVISO_SEM_LOTACAO);

        $this->assertSame($antes, Pedidos::query()->count());
    }
}
