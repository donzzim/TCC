<?php

namespace Tests\Feature\MobileApi;

use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use App\Models\egap\Patrimonio\BensMoveis\ItemInventario;
use App\Models\egap\User;
use App\Services\Egap\Mobile\ConferenciaBensService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\MobileApi\Concerns\UsaSessaoMobile;
use Tests\TestCase;

class ConferenciaTest extends TestCase
{
    use DatabaseTransactions;
    use UsaSessaoMobile;

    protected array $connectionsToTransact = ['emes', 'egap'];

    /**
     * @return array{0: array, 1: string, 2: array}  escopo, token, bem pendente (id, patrimonio)
     */
    private function cenario(): array
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

            if ($escopo === null) {
                continue;
            }

            $dados = app(ConferenciaBensService::class)->bens($escopo, 'pendente', 1);
            $inventario = Inventario::query()->find($dados['inventario']['id']);

            if ($inventario === null
                || ! $inventario->estaAtivo()
                || ! $dados['atividade']['pode_editar']
                || $dados['bens']->isEmpty()) {
                continue;
            }

            $bem = $dados['bens']->first();

            if (blank($bem['patrimonio'])) {
                continue;
            }

            return [$escopo, $plain, ['id' => (int) $bem['id'], 'patrimonio' => (string) $bem['patrimonio']]];
        }

        $this->markTestSkipped('nenhum usuário com inventário aberto, atividade editável e bem pendente no setor');
    }

    private function itensDoBem(int $bemId): int
    {
        return ItemInventario::query()->where('id_bem', $bemId)->count();
    }

    public function test_leitura_localiza_e_segunda_leitura_e_ja_conferido(): void
    {
        [, $plain, $bem] = $this->cenario();
        $antes = $this->itensDoBem($bem['id']);

        $this->withToken($plain)->postJson('/mobile-api/conferencia/leituras', ['codigo' => $bem['patrimonio']])
            ->assertOk()
            ->assertJsonPath('status', 'localizado')
            ->assertJsonStructure(['resumo']);

        $this->withToken($plain)->postJson('/mobile-api/conferencia/leituras', ['codigo' => $bem['patrimonio']])
            ->assertOk()
            ->assertJsonPath('status', 'ja_conferido')
            ->assertJsonStructure(['resumo']);

        $this->assertSame($antes + 1, $this->itensDoBem($bem['id']));
    }

    public function test_leitura_aceita_codigo_com_espacos_e_pontuacao(): void
    {
        [$escopo, $plain, $bem] = $this->cenario();

        $esperado = app(ConferenciaBensService::class)->validarLeitura($escopo, $bem['patrimonio']);
        $formatado = number_format((int) $bem['patrimonio'], 0, ',', '.');

        $this->withToken($plain)->postJson('/mobile-api/conferencia/leituras', ['codigo' => " {$formatado} "])
            ->assertOk()
            ->assertJsonPath('bem.id', $esperado['bem']['id']);
    }

    public function test_leitura_de_bem_de_outro_setor(): void
    {
        [$escopo, $plain] = $this->cenario();

        $outro = BemMovel::query()
            ->where('Setor', '!=', $escopo['setor'])
            ->whereNotNull('NumPatrimonio')
            ->where('NumPatrimonio', '>', 0)
            ->first();

        if ($outro === null) {
            $this->markTestSkipped('nenhum bem de outro setor');
        }

        $antes = ItemInventario::query()->count();

        $this->withToken($plain)->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $outro->NumPatrimonio])
            ->assertOk()
            ->assertJsonPath('status', 'outro_setor');

        $this->assertSame($antes, ItemInventario::query()->count());
    }

    public function test_codigo_inexistente(): void
    {
        [, $plain] = $this->cenario();

        $this->withToken($plain)->postJson('/mobile-api/conferencia/leituras', ['codigo' => 'XX-inexistente-999'])
            ->assertOk()
            ->assertJsonPath('status', 'nao_cadastrado');
    }

    public function test_leitura_sem_codigo_422(): void
    {
        [, $plain] = $this->cenario();

        $this->withToken($plain)->postJson('/mobile-api/conferencia/leituras', [])->assertStatus(422);
    }

    public function test_lista_por_status(): void
    {
        [, $plain] = $this->cenario();

        $this->withToken($plain)->getJson('/mobile-api/conferencia/bens?status=pendente')
            ->assertOk()
            ->assertJsonStructure(['bens', 'resumo', 'meta' => ['current_page', 'per_page', 'total', 'last_page', 'has_more']]);
    }

    public function test_atual_na_raiz_da_conferencia(): void
    {
        [, $plain] = $this->cenario();

        $this->withToken($plain)->getJson('/mobile-api/conferencia')
            ->assertOk()
            ->assertJsonStructure(['inventario', 'atividade', 'resumo']);
    }

    public function test_nao_localizados_registra_item(): void
    {
        [, $plain, $bem] = $this->cenario();
        $antes = $this->itensDoBem($bem['id']);

        $this->withToken($plain)->postJson('/mobile-api/conferencia/nao-localizados', [
            'bem_ids' => [$bem['id']],
            'justificativa' => 'não encontrado no teste',
        ])->assertOk();

        $this->assertSame($antes + 1, $this->itensDoBem($bem['id']));
    }

    public function test_divergencia_exige_bem_ou_codigo(): void
    {
        [, $plain] = $this->cenario();

        $this->withToken($plain)->postJson('/mobile-api/conferencia/divergencias', ['observacao' => 'x'])
            ->assertStatus(422);
    }

    public function test_rotas_antigas_removidas(): void
    {
        [, $plain] = $this->cenario();

        $this->withToken($plain)->getJson('/mobile-api/conferencia/atual')->assertNotFound();
    }

    public function test_conferencia_exige_sessao(): void
    {
        $this->getJson('/mobile-api/conferencia')->assertUnauthorized();
    }
}
