<?php

namespace Tests\Feature\MobileApi;

use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\User;
use App\Models\egap\UserLotacao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\MobileApi\Concerns\UsaSessaoMobile;
use Tests\TestCase;

class BensTest extends TestCase
{
    use DatabaseTransactions;
    use UsaSessaoMobile;

    protected array $connectionsToTransact = ['emes', 'egap'];

    private function usuarioComBens(): array
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

            if ($escopo !== null && BemMovel::query()
                ->where('UnidadeJudiciaria', $escopo['unidade_judiciaria'])
                ->where('Setor', $escopo['setor'])
                ->whereIn('SituacaoBem', [1, 7, 8])
                ->exists()) {
                return [$user, $sessao, $plain];
            }
        }

        $this->markTestSkipped('nenhum usuário com egap_access e bens elegíveis no setor neste banco');
    }

    public function test_bens_so_do_setor_da_sessao(): void
    {
        [, $sessao, $plain] = $this->usuarioComBens();

        $itens = $this->withToken($plain)->getJson('/mobile-api/bens?per_page=50')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page', 'has_more']])
            ->json('data');

        $this->assertNotEmpty($itens);

        foreach ($itens as $item) {
            $this->assertSame($sessao->setorAtual(), (int) $item['setor']['id']);
            $this->assertContains((int) $item['situacao_id'], [1, 7, 8]);
        }
    }

    public function test_bens_mudam_ao_trocar_setor(): void
    {
        $user = $this->usuarioComVariasLotacoes();
        [$sessao, $plain] = $this->sessaoPara($user);

        $outro = $sessao->lotacoes()->first(function ($setor) use ($sessao) {
            return (int) $setor->id !== $sessao->setorAtual()
                && BemMovel::query()->where('Setor', $setor->id)->whereIn('SituacaoBem', [1, 7, 8])->exists();
        });

        if ($outro === null) {
            $this->markTestSkipped('nenhuma outra lotação com bens elegíveis');
        }

        $this->withToken($plain)->putJson('/mobile-api/sessao/setor', ['setor_id' => $outro->id])->assertOk();

        $primeiro = $this->withToken($plain)->getJson('/mobile-api/bens?per_page=5')->assertOk()->json('data.0');

        $this->assertSame((int) $outro->id, (int) $primeiro['setor']['id']);
    }

    public function test_per_page_limitado(): void
    {
        [, , $plain] = $this->usuarioComBens();

        $this->withToken($plain)->getJson('/mobile-api/bens?per_page=500')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 50);
    }

    public function test_show_bem_de_outro_setor_marca_flag(): void
    {
        [, $sessao, $plain] = $this->usuarioComBens();

        $outro = BemMovel::query()
            ->where('Setor', '!=', $sessao->setorAtual())
            ->whereNotNull('NumPatrimonio')
            ->where('NumPatrimonio', '!=', '')
            ->first();

        if ($outro === null) {
            $this->markTestSkipped('nenhum bem de outro setor');
        }

        $this->withToken($plain)->getJson('/mobile-api/bens/'.rawurlencode((string) $outro->NumPatrimonio))
            ->assertOk()
            ->assertJsonPath('data.pertence_ao_setor', false);

        $this->withToken($plain)->getJson('/mobile-api/bens/inexistente')->assertNotFound();
    }

    public function test_show_bem_do_setor_marca_pertence(): void
    {
        [, $sessao, $plain] = $this->usuarioComBens();
        $escopo = $sessao->escopo();

        $bem = BemMovel::query()
            ->where('UnidadeJudiciaria', $escopo['unidade_judiciaria'])
            ->where('Setor', $escopo['setor'])
            ->whereIn('SituacaoBem', [1, 7, 8])
            ->first();

        $this->withToken($plain)->getJson('/mobile-api/bens/'.rawurlencode((string) $bem->NumPatrimonio))
            ->assertOk()
            ->assertJsonPath('data.pertence_ao_setor', true)
            ->assertJsonPath('data.situacao_elegivel', true);
    }

    public function test_dashboard_formato(): void
    {
        [, , $plain] = $this->usuarioComBens();

        $this->withToken($plain)->getJson('/mobile-api/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'bens' => ['total', 'situacoes'],
                'financeiro' => ['valor_aquisicao', 'valor_atual', 'sem_valor'],
                'pedidos_abertos',
                'conferencia',
            ])
            ->assertJsonMissingPath('scope');
    }

    public function test_sem_lotacao_409(): void
    {
        $candidato = User::query()
            ->where('ativo', 1)
            ->doesntHave('setores')
            ->orderBy('id')
            ->limit(300)
            ->get()
            ->first(fn (User $user) => $this->temAcessoEgap($user));

        if ($candidato === null) {
            $this->markTestSkipped('nenhum usuário com acesso e sem lotação');
        }

        [, $plain] = $this->sessaoPara($candidato);

        $resposta = $this->withToken($plain)->getJson('/mobile-api/bens');

        if ($resposta->status() === 200) {
            $this->markTestSkipped('usuário resolve lotação legada');
        }

        $resposta->assertStatus(409)->assertJsonPath('code', 'sem_lotacao');
    }
}
