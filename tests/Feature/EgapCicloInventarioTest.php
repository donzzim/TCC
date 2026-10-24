<?php

namespace Tests\Feature;

use App\Filament\Egap\Externo\Groups\Patrimonio\Components\BensNoSetorTable;
use App\Helper\CpfHelper;
use App\Jobs\Egap\NotificarAberturaInventario;
use App\Models\egap\Admin\InfoUser;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensMoveis\AtividadeInventario;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use App\Models\egap\User;
use App\Models\egap\UserEgap;
use App\Models\egap\UserLotacao;
use App\Services\Egap\Inventario\NotificadorInventario;
use Carbon\CarbonImmutable;
use Database\Seeders\Spatie\EgapRolesPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\MobileApi\Concerns\UsaSessaoMobile;
use Tests\TestCase;

/**
 * Ciclo de um inventário de ponta a ponta, com setor, bens e usuário fictícios:
 * criação, aviso de abertura, lembrete, conferência pelo app, finalização do
 * setor (app e tela Bens no Setor) e conclusão do inventário.
 *
 * Roda contra o banco real; tudo acontece dentro de uma transação desfeita no fim.
 */
class EgapCicloInventarioTest extends TestCase
{
    use DatabaseTransactions;
    use UsaSessaoMobile;

    protected array $connectionsToTransact = ['emes', 'egap'];

    private Setores $setor;

    /** @var array<int, BemMovel> */
    private array $bens = [];

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EgapRolesPermissionsSeeder::class);

        $this->setor = $this->setorSemBens();
        $this->bens = [$this->novoBem(), $this->novoBem(), $this->novoBem()];
        $this->user = $this->usuarioDoSetor();
        [, $this->token] = $this->sessaoPara($this->user);
    }

    /** Setor inventariável sem bens conferíveis: o resumo do setor passa a ser só dos bens fictícios. */
    private function setorSemBens(): Setores
    {
        $candidatos = Setores::query()->setoresInventariaveis()->whereNotNull('codigodauo')->orderBy('id')->limit(300)->get();

        foreach ($candidatos as $setor) {
            $temBens = BemMovel::query()
                ->where('UnidadeJudiciaria', $setor->codigodauo)
                ->where('Setor', $setor->id)
                ->whereIn('SituacaoBem', [1, 7, 8])
                ->exists();

            if (! $temBens) {
                return $setor;
            }
        }

        $this->markTestSkipped('nenhum setor inventariável sem bens neste banco');
    }

    private function novoBem(): BemMovel
    {
        do {
            $patrimonio = random_int(980000000, 989999999);
        } while (BemMovel::query()->where('NumPatrimonio', $patrimonio)->exists());

        return BemMovel::query()->create([
            'NumPatrimonio' => $patrimonio,
            'Descricao' => 'BEM FICTÍCIO DE TESTE',
            'SituacaoBem' => 1,
            'UnidadeJudiciaria' => $this->setor->codigodauo,
            'Setor' => $this->setor->id,
        ]);
    }

    private function usuarioDoSetor(): User
    {
        $sufixo = Str::lower(Str::random(10));
        $cpf = '999'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        $user = User::query()->create([
            'login' => "teste.{$sufixo}",
            'name' => "Usuário Teste {$sufixo}",
            'email' => "teste.{$sufixo}@example.invalid",
            'password' => bcrypt(Str::random(16)),
            'cpf' => $cpf,
            'ativo' => 1,
        ]);

        UserLotacao::query()->create([
            'user_id' => $user->id,
            'setor_id' => $this->setor->id,
            'atualizado_por' => $user->id,
            'principal' => true,
        ]);

        // id do EGAP legado (pelo CPF): é quem fica registrado nos itens conferidos
        $egap = UserEgap::query()->create(['name' => $user->name, 'username' => "teste.{$sufixo}", 'params' => '']);
        InfoUser::query()->create(['usuario_id' => $egap->id, 'cpf' => CpfHelper::format($cpf)]);

        $user->assignRole(User::ROLE_USER);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    private function novoInventario(string $situacao, ?CarbonImmutable $prazo): Inventario
    {
        $inventario = Inventario::query()->create([
            'num_inventario' => 98,
            'ano_inventario' => 2026,
            'situacao' => $situacao,
        ]);

        $inventario->unidadesInventariadas()->create([
            'unidades' => $this->setor->id,
            'data_termino' => $prazo?->format('Y-m-d 00:00:00'),
            'situacao' => Inventario::SITUACAO_A_INVENTARIAR,
        ]);

        return $inventario;
    }

    /** Deixa todos os bens fictícios tratados: dois lidos pelo app e um não localizado. */
    private function conferirTudo(): void
    {
        [$a, $b, $c] = $this->bens;

        foreach ([$a, $b] as $bem) {
            $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $bem->NumPatrimonio])
                ->assertOk()
                ->assertJsonPath('status', 'localizado');
        }

        $this->api()->postJson('/mobile-api/conferencia/nao-localizados', [
            'bem_ids' => [$c->id],
            'justificativa' => 'Não encontrado na sala.',
        ])->assertSuccessful();
    }

    private function api(): static
    {
        return $this->withToken($this->token);
    }

    private function notificacoes(string $tipo)
    {
        return $this->user->notifications()->get()->filter(fn ($n): bool => ($n->data['viewData']['tipo'] ?? null) === $tipo)->values();
    }

    public function test_ciclo_completo_pelo_app(): void
    {
        $hoje = CarbonImmutable::today();
        $prazo = $hoje->addDays(7);

        // abrir avisaria os lotados reais do setor depois da resposta; o job roda à mão logo abaixo
        Bus::fake([NotificarAberturaInventario::class]);

        // 1. criado "A inventariar": ninguém é avisado ainda
        $inventario = $this->novoInventario(Inventario::SITUACAO_A_INVENTARIAR, $prazo);
        Bus::assertNotDispatched(NotificarAberturaInventario::class);

        $this->api()->getJson('/mobile-api/conferencia')
            ->assertOk()
            ->assertJsonPath('inventario.id', $inventario->id)
            ->assertJsonPath('resumo.total', 3)
            ->assertJsonPath('resumo.pendentes', 3)
            ->assertJsonPath('atividade.pode_finalizar', false);

        // 2. passou para "Em execução": dispara o aviso de abertura
        $inventario->update(['situacao' => Inventario::SITUACAO_EM_EXECUCAO]);
        Bus::assertDispatched(NotificarAberturaInventario::class, fn ($job): bool => $job->inventarioId === $inventario->id);
        (new NotificarAberturaInventario($inventario->id))->handle();

        $abertura = $this->notificacoes('inventario_aberto');
        $this->assertCount(1, $abertura);
        $this->assertSame('Inventário 98/2026 em andamento — Prazo: '.$prazo->format('d/m/Y'), $abertura->first()->data['title']);

        $this->api()->getJson('/mobile-api/notificacoes')
            ->assertOk()
            ->assertJsonPath('meta.nao_lidas', 1)
            ->assertJsonPath('notificacoes.0.tipo', 'inventario_aberto')
            ->assertJsonPath('notificacoes.0.rota_app', '/tutorial');

        // 3. faltam 7 dias: lembrete
        NotificadorInventario::notificarLembretes($hoje);
        $lembretes = $this->notificacoes('inventario_lembrete');
        $this->assertCount(1, $lembretes);
        $this->assertSame('Inventário 98/2026: faltam 7 dias — Prazo: '.$prazo->format('d/m/Y'), $lembretes->first()->data['title']);

        // 4. não finaliza com bens pendentes
        $this->api()->postJson('/mobile-api/conferencia/finalizar')->assertStatus(409);

        // 5. confere tudo
        $this->conferirTudo();

        $this->api()->getJson('/mobile-api/conferencia')
            ->assertOk()
            ->assertJsonPath('resumo.pendentes', 0)
            ->assertJsonPath('resumo.localizados', 2)
            ->assertJsonPath('resumo.nao_localizados', 1)
            ->assertJsonPath('atividade.pode_finalizar', true);

        // 6. finaliza o setor
        $this->api()->postJson('/mobile-api/conferencia/finalizar')
            ->assertOk()
            ->assertJsonPath('atividade.situacao', 'Finalizado')
            ->assertJsonPath('atividade.pode_editar', false)
            ->assertJsonPath('atividade.qtde_inventariada', 3);

        $atividade = AtividadeInventario::query()->doInventario($inventario)->where('setor', $this->setor->id)->sole();
        $this->assertTrue($atividade->estaFinalizada());
        $this->assertNotNull($atividade->termino);

        // 7. setor finalizado não aceita mais conferência nem nova finalização; reler um bem já conferido só informa
        $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $this->bens[0]->NumPatrimonio])
            ->assertOk()
            ->assertJsonPath('status', 'ja_conferido');

        $chegouDepois = $this->novoBem();
        $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $chegouDepois->NumPatrimonio])->assertForbidden();
        $this->api()->postJson('/mobile-api/conferencia/nao-localizados', [
            'bem_ids' => [$chegouDepois->id],
            'justificativa' => 'Teste.',
        ])->assertForbidden();
        $this->api()->postJson('/mobile-api/conferencia/finalizar')->assertForbidden();

        // 8. véspera do prazo: setor finalizado não recebe o lembrete de 1 dia
        NotificadorInventario::notificarLembretes($prazo->subDay());
        $this->assertCount(1, $this->notificacoes('inventario_lembrete'));

        // 9. lê os avisos no app
        $this->api()->postJson('/mobile-api/notificacoes/lidas')->assertNoContent();
        $this->api()->getJson('/mobile-api/notificacoes')->assertOk()->assertJsonPath('meta.nao_lidas', 0);
    }

    public function test_setor_sem_finalizar_recebe_o_lembrete_da_vespera(): void
    {
        $hoje = CarbonImmutable::today();
        $this->novoInventario(Inventario::SITUACAO_EM_EXECUCAO, $hoje->addDay());

        // conferiu tudo, mas não clicou em finalizar: ainda é avisado
        $this->conferirTudo();
        NotificadorInventario::notificarLembretes($hoje);

        $lembretes = $this->notificacoes('inventario_lembrete');
        $this->assertCount(1, $lembretes);
        $this->assertStringContainsString('falta 1 dia', $lembretes->first()->data['title']);
    }

    public function test_finalizar_pela_tela_bens_no_setor(): void
    {
        Bus::fake([NotificarAberturaInventario::class]);
        $inventario = $this->novoInventario(Inventario::SITUACAO_EM_EXECUCAO, CarbonImmutable::today()->addDays(10));

        $this->actingAs($this->user, User::GUARD);
        Filament::setCurrentPanel('egap');

        // com bens pendentes o botão não aparece
        Livewire::test(BensNoSetorTable::class)->assertActionHidden('finalizarInventario');

        $this->conferirTudo();

        Livewire::test(BensNoSetorTable::class)
            ->assertActionVisible('finalizarInventario')
            ->callAction('finalizarInventario')
            ->assertNotified('Inventário finalizado com sucesso!');

        $atividade = AtividadeInventario::query()->doInventario($inventario)->where('setor', $this->setor->id)->sole();
        $this->assertTrue($atividade->estaFinalizada());

        // depois de finalizado o botão some
        Livewire::test(BensNoSetorTable::class)->assertActionHidden('finalizarInventario');
    }

    public function test_inventario_concluido_encerra_a_conferencia(): void
    {
        $hoje = CarbonImmutable::today();
        Bus::fake([NotificarAberturaInventario::class]);
        $inventario = $this->novoInventario(Inventario::SITUACAO_EM_EXECUCAO, $hoje->addDays(7));

        // sem outro inventário ativo no banco, o concluído continua sendo o "atual" do app
        Inventario::query()->ativos()->whereKeyNot($inventario->id)->update(['situacao' => Inventario::SITUACAO_CONCLUIDO]);

        $inventario->update(['situacao' => Inventario::SITUACAO_CONCLUIDO]);

        // concluído não manda lembrete
        $this->assertSame(0, NotificadorInventario::notificarLembretes($hoje));
        $this->assertCount(0, $this->notificacoes('inventario_lembrete'));

        $this->api()->getJson('/mobile-api/conferencia')
            ->assertOk()
            ->assertJsonPath('inventario.id', $inventario->id)
            ->assertJsonPath('inventario.situacao', 'Concluído')
            ->assertJsonPath('atividade.pode_editar', false)
            ->assertJsonPath('atividade.pode_finalizar', false);

        $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $this->bens[0]->NumPatrimonio])->assertForbidden();
        $this->api()->postJson('/mobile-api/conferencia/finalizar')->assertForbidden();
    }
}
