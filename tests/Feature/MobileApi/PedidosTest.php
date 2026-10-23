<?php

namespace Tests\Feature\MobileApi;

use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\MobileApi\Concerns\UsaSessaoMobile;
use Tests\TestCase;

class PedidosTest extends TestCase
{
    use DatabaseTransactions;
    use UsaSessaoMobile;

    protected array $connectionsToTransact = ['emes', 'egap'];

    /**
     * @return array{0: \App\Filament\Egap\Auth\MobileEgapSession, 1: string}
     */
    private function sessaoComEgapId(): array
    {
        $candidatos = User::query()
            ->where('ativo', 1)
            ->whereNotNull('cpf')
            ->has('setores')
            ->orderBy('id')
            ->limit(300)
            ->get();

        foreach ($candidatos as $user) {
            if (! $this->temAcessoEgap($user)) {
                continue;
            }

            [$sessao, $plain] = $this->sessaoPara($user);
            $escopo = $sessao->escopo();

            if ($escopo !== null && $escopo['id_egap'] !== null) {
                return [$sessao, $plain];
            }
        }

        $this->markTestSkipped('nenhum usuário com egap_access, lotação e egap_id neste banco');
    }

    public function test_exige_sessao(): void
    {
        $this->getJson('/mobile-api/pedidos')->assertUnauthorized();
    }

    public function test_contexto_e_materiais(): void
    {
        [, $plain] = $this->sessaoComEgapId();

        $this->withToken($plain)->getJson('/mobile-api/pedidos/contexto')
            ->assertOk()
            ->assertJsonStructure(['complementos']);

        $this->withToken($plain)->getJson('/mobile-api/pedidos/materiais?tipo=consumo')
            ->assertOk()
            ->assertJsonStructure(['materiais', 'meta' => ['current_page', 'per_page', 'total', 'last_page', 'has_more']]);
    }

    public function test_materiais_exige_tipo_valido(): void
    {
        [, $plain] = $this->sessaoComEgapId();

        $this->withToken($plain)->getJson('/mobile-api/pedidos/materiais?tipo=outro')
            ->assertStatus(422)
            ->assertJsonValidationErrors('tipo');
    }

    /**
     * @return array{0: array, 1: int}
     */
    private function payloadConsumo(string $plain): array
    {
        $materiais = $this->withToken($plain)->getJson('/mobile-api/pedidos/materiais?tipo=consumo&per_page=5')
            ->assertOk()->json('materiais');
        $complementos = $this->withToken($plain)->getJson('/mobile-api/pedidos/contexto')
            ->assertOk()->json('complementos');

        if ($materiais === [] || $complementos === []) {
            $this->markTestSkipped('catálogo de consumo ou complementos vazios neste banco');
        }

        return [[
            'tipo' => 'consumo',
            'complemento_setor_id' => $complementos[0]['id'],
            'justificativa' => 'Teste automatizado da API mobile',
            'itens' => [['material_id' => $materiais[0]['id'], 'quantidade' => 1]],
        ], (int) $materiais[0]['id']];
    }

    public function test_cria_pedido_consumo(): void
    {
        [$sessao, $plain] = $this->sessaoComEgapId();
        [$payload] = $this->payloadConsumo($plain);

        $id = $this->withToken($plain)->postJson('/mobile-api/pedidos', $payload)
            ->assertCreated()
            ->assertJsonPath('pedido.tipo', 'consumo')
            ->json('pedido.id');

        $pedido = Pedidos::query()->findOrFail($id);

        $this->assertSame($sessao->setorAtual(), (int) $pedido->Setor);
        $this->assertSame($sessao->resolverAtual(), (int) $pedido->Solicitante);
    }

    public function test_pedido_ignora_setor_enviado_pelo_cliente(): void
    {
        [$sessao, $plain] = $this->sessaoComEgapId();
        [$payload] = $this->payloadConsumo($plain);

        $id = $this->withToken($plain)
            ->postJson('/mobile-api/pedidos', $payload + ['setor' => 999999, 'unidade_judiciaria' => 1, 'Solicitante' => 1])
            ->assertCreated()
            ->json('pedido.id');

        $pedido = Pedidos::query()->findOrFail($id);

        $this->assertSame($sessao->setorAtual(), (int) $pedido->Setor);
        $this->assertSame($sessao->unidadeAtual(), (int) $pedido->UnidadeJudiciaria);
        $this->assertSame($sessao->resolverAtual(), (int) $pedido->Solicitante);
    }

    public function test_historico_do_usuario(): void
    {
        [, $plain] = $this->sessaoComEgapId();

        $this->withToken($plain)->getJson('/mobile-api/pedidos')
            ->assertOk()
            ->assertJsonStructure(['pedidos', 'meta' => ['current_page', 'total', 'has_more']]);
    }
}
