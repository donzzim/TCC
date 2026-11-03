<?php

namespace Tests\Feature;

use App\Filament\Egap\Externo\Groups\Patrimonio\Components\AtividadeDeCampoNaoLocalizadosTable;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\BensNoSetorTable;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\LevantamentoComissaoTable;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\AtividadeInventarioResource\Pages\ListAtividadeInventarios;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioUnidadeResource;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\Pages\EditInventario;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\Pages\ListInventarios;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ItemInventarioResource\Pages\ListItemInventarios;
use App\Models\egap\Patrimonio\BensMoveis\InventarioComissao;
use App\Services\Egap\Inventario\AbrangenciaInventario;
use App\Services\Egap\Mobile\ConferenciaBensService;
use Filament\Actions\Testing\TestAction;
use App\Helper\CpfHelper;
use App\Jobs\Egap\NotificarAberturaInventario;
use App\Models\egap\Admin\InfoUser;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensMoveis\AtividadeInventario;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use App\Models\egap\Patrimonio\BensMoveis\ItemInventario;
use App\Models\egap\User;
use App\Models\egap\UserEgap;
use App\Models\egap\UserLotacao;
use App\Services\Egap\Inventario\NotificadorInventario;
use Carbon\CarbonImmutable;
use Database\Seeders\Spatie\EgapRolesPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\LimpaTabelasMyIsamDoInventario;
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
    use LimpaTabelasMyIsamDoInventario;
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

    // ---- etapa 4 da revisão do inventário ----

    public function test_sem_vinculo_com_o_egap_nao_grava(): void
    {
        $this->inventarioAberto();
        $sufixo = Str::lower(Str::random(10));
        $semEgap = User::query()->create([
            'login' => "teste.{$sufixo}",
            'name' => "Usuário Teste {$sufixo}",
            'email' => "teste.{$sufixo}@example.invalid",
            'password' => bcrypt(Str::random(16)),
            'cpf' => '999'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'ativo' => 1,
        ]);
        UserLotacao::query()->create(['user_id' => $semEgap->id, 'setor_id' => $this->setor->id, 'atualizado_por' => $semEgap->id, 'principal' => true]);
        $semEgap->assignRole(User::ROLE_USER);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        [, $token] = $this->sessaoPara($semEgap->fresh());

        $this->withToken($token)->getJson('/mobile-api/conferencia')
            ->assertOk()
            ->assertJsonPath('atividade.pode_editar', false)
            ->assertJsonPath('atividade.bloqueio.code', 'sem_vinculo_egap');

        $this->withToken($token)->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $this->bens[0]->NumPatrimonio])
            ->assertForbidden();
    }

    public function test_bens_no_setor_le_patrimonio_e_registra_divergencia(): void
    {
        $this->inventarioAberto();
        [$a, $b] = $this->bens;
        $this->actingAs($this->user, User::GUARD);
        Filament::setCurrentPanel('egap');

        Livewire::test(BensNoSetorTable::class)
            ->assertSee('Pendentes: 3')
            ->callAction('lerPatrimonio', ['codigo' => (string) $a->NumPatrimonio])
            ->assertNotified('Bem localizado no setor.');

        Livewire::test(BensNoSetorTable::class)
            ->callAction(TestAction::make('divergencia')->table($b), [
                'campos' => ['serie'],
                'valores' => ['serie' => 'SERIE-WEB'],
                'observacao' => 'Etiqueta de série diferente.',
            ])
            ->assertNotified('Divergência registrada.');

        $item = ItemInventario::query()->where('id_bem', $b->id)->sole();
        $this->assertSame('DIVERGENTE', $item->situacao);
        $this->assertSame('SERIE-WEB', $item->num_serie);
        $this->assertStringContainsString('Número de série', (string) $item->observacao);

        Livewire::test(BensNoSetorTable::class)->assertSee('Pendentes: 1');
    }

    public function test_datas_do_inventario_vao_para_as_unidades_que_tinham_a_data_antiga(): void
    {
        $inventario = Inventario::query()->create([
            'num_inventario' => 98,
            'ano_inventario' => 2026,
            'situacao' => Inventario::SITUACAO_A_INVENTARIAR,
            'inicio_inventario' => '2026-11-01 00:00:00',
            'termino_inventario' => '2026-11-30 00:00:00',
        ]);
        $igual = $inventario->unidadesInventariadas()->create(['unidades' => $this->setor->id, 'data_inicio' => '2026-11-01 00:00:00', 'data_termino' => '2026-11-30 00:00:00', 'situacao' => '0']);
        $propria = $inventario->unidadesInventariadas()->create(['unidades' => $this->setor->id, 'data_inicio' => '2026-11-01 00:00:00', 'data_termino' => '2026-11-15 00:00:00', 'situacao' => '0']);

        $inventario->update(['termino_inventario' => '2026-12-10 00:00:00']);

        $this->assertSame('2026-12-10', $igual->fresh()->data_termino->toDateString());
        $this->assertSame(39, (int) $igual->fresh()->dias);
        // prazo próprio da unidade não muda
        $this->assertSame('2026-11-15', $propria->fresh()->data_termino->toDateString());
    }

    public function test_unidade_de_inventario_com_conferencia_nao_pode_ser_excluida(): void
    {
        $inventario = $this->inventarioAberto();
        $unidade = $inventario->unidadesInventariadas()->sole();
        $this->noPainelCom(['egap_inventario']);

        $this->assertTrue(InventarioUnidadeResource::canDelete($unidade));

        $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $this->bens[0]->NumPatrimonio]);

        $this->assertFalse(InventarioUnidadeResource::canDelete($unidade->fresh()));
        $this->assertFalse(InventarioResource::canDelete($inventario->fresh()));
    }

    // ---- etapa 3 da revisão do inventário ----

    public function test_total_conferivel_ignora_bens_que_nunca_ficam_pendentes(): void
    {
        $inventario = Inventario::query()->create(['num_inventario' => 98, 'ano_inventario' => 2026, 'situacao' => Inventario::SITUACAO_A_INVENTARIAR]);
        $inventario->unidadesInventariadas()->create(['unidades' => $this->setor->id, 'situacao' => Inventario::SITUACAO_A_INVENTARIAR]);
        Bus::fake([NotificarAberturaInventario::class]);
        $inventario->update(['situacao' => Inventario::SITUACAO_EM_EXECUCAO]);

        // em transferência: entra no total, mas nunca fica pendente
        $this->bens[2]->forceFill(['SituacaoBem' => 7])->saveQuietly();

        $this->api()->getJson('/mobile-api/conferencia')
            ->assertJsonPath('resumo.total', 3)
            ->assertJsonPath('resumo.pendentes', 2)
            ->assertJsonPath('resumo.total_conferivel', 2);

        $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $this->bens[0]->NumPatrimonio]);

        $this->api()->getJson('/mobile-api/conferencia')
            ->assertJsonPath('resumo.pendentes', 1)
            ->assertJsonPath('resumo.total_conferivel', 2);
    }

    public function test_nao_localizados_de_todos_os_pendentes(): void
    {
        $this->inventarioAberto();
        $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $this->bens[0]->NumPatrimonio]);

        $this->api()->postJson('/mobile-api/conferencia/nao-localizados', [
            'todos_pendentes' => true,
            'justificativa' => 'Sala trancada.',
        ])
            ->assertSuccessful()
            ->assertJsonPath('registrados', 2)
            ->assertJsonPath('ignorados', 0);

        $this->api()->getJson('/mobile-api/conferencia')
            ->assertJsonPath('resumo.pendentes', 0)
            ->assertJsonPath('resumo.localizados', 1)
            ->assertJsonPath('resumo.nao_localizados', 2);

        // sem lista e sem "todos": erro de validação, não vira "todos"
        $this->api()->postJson('/mobile-api/conferencia/nao-localizados', [
            'todos_pendentes' => false,
            'justificativa' => 'x',
        ])->assertUnprocessable()->assertJsonValidationErrors('bem_ids');
    }

    /** O usuário fictício entra no painel com as roles pedidas. */
    private function noPainelCom(array $roles): void
    {
        $this->user->syncRoles([User::ROLE_USER, ...$roles]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($this->user->fresh(), User::GUARD);
        Filament::setCurrentPanel('egap');
    }

    private function inventarioAberto(): Inventario
    {
        Bus::fake([NotificarAberturaInventario::class]);

        return $this->novoInventario(Inventario::SITUACAO_EM_EXECUCAO, CarbonImmutable::today()->addDays(10));
    }

    // ---- etapa 2 da revisão do inventário ----

    public function test_divergencia_grava_os_valores_encontrados_e_o_levantamento_aplica_no_bem(): void
    {
        $this->inventarioAberto();
        $bem = $this->bens[0];
        $bem->forceFill(['NumerodeSerie' => 'ANTIGO-1', 'EstadodeConservacao' => 'BOM'])->saveQuietly();

        $this->api()->postJson('/mobile-api/conferencia/divergencias', [
            'bem_id' => $bem->id,
            'campos' => ['Número de série', 'Estado de conservação', 'Marca'],
            'valores' => ['Número de série' => 'NOVO-2', 'Estado de conservação' => 'regular', 'Marca' => 'MARCA QUE NAO EXISTE XYZ'],
            'observacao' => 'Plaqueta trocada.',
        ])->assertCreated()->assertJsonPath('status', 'divergente');

        $item = ItemInventario::query()->where('id_bem', $bem->id)->sole();
        $this->assertSame('DIVERGENTE', $item->situacao);
        $this->assertSame('NOVO-2', $item->num_serie);
        $this->assertSame('ANTIGO-1', $item->num_serie_egap);
        $this->assertSame('REGULAR', $item->estado_conservacao);

        $this->noPainelCom(['egap_patrimonio']);
        Livewire::test(ListItemInventarios::class)
            ->callAction(TestAction::make('finalizar_levantamento')->table($item))
            ->assertNotified('Levantamento finalizado com sucesso.');

        $bem->refresh();
        $this->assertSame('NOVO-2', $bem->NumerodeSerie);
        $this->assertSame('REGULAR', $bem->EstadodeConservacao);
        // marca digitada fora do catálogo não apaga a do bem
        $this->assertNull($bem->Marca);
        $this->assertNotNull($item->fresh()->transferido_em);
    }

    public function test_divergencia_de_bem_sem_cadastro_nao_finaliza_levantamento(): void
    {
        $this->inventarioAberto();

        $this->api()->postJson('/mobile-api/conferencia/divergencias', [
            'codigo' => '987654321012',
            'observacao' => 'Achado sem plaqueta no sistema.',
        ])->assertCreated();

        $item = ItemInventario::query()->where('num_patrimonio', '987654321012')->whereNull('id_bem')->sole();

        $this->noPainelCom(['egap_patrimonio']);
        Livewire::test(ListItemInventarios::class)
            ->callAction(TestAction::make('finalizar_levantamento')->table($item))
            ->assertNotified('Levantamento não finalizado.');

        $this->assertNull($item->fresh()->getRawOriginal('transferido_em'));
    }

    public function test_observacao_e_justificativa_respeitam_255_caracteres(): void
    {
        $this->inventarioAberto();

        $this->api()->postJson('/mobile-api/conferencia/nao-localizados', [
            'bem_ids' => [$this->bens[0]->id],
            'justificativa' => str_repeat('a', 256),
        ])->assertUnprocessable();

        $this->api()->postJson('/mobile-api/conferencia/divergencias', [
            'bem_id' => $this->bens[0]->id,
            'observacao' => str_repeat('a', 256),
        ])->assertUnprocessable();

        // 255 de justificativa + o texto de "reencontrado" continua cabendo na coluna
        $this->api()->postJson('/mobile-api/conferencia/nao-localizados', [
            'bem_ids' => [$this->bens[0]->id],
            'justificativa' => str_repeat('b', 255),
        ])->assertSuccessful();
        $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $this->bens[0]->NumPatrimonio])
            ->assertJsonPath('status', 'relocalizado');

        $this->assertLessThanOrEqual(255, mb_strlen((string) ItemInventario::query()->where('id_bem', $this->bens[0]->id)->sole()->observacao));
    }

    public function test_telas_web_mostram_a_situacao_dos_itens_do_inventario(): void
    {
        $inventario = $this->inventarioAberto();
        [$a, $b, $c] = $this->bens;
        // valor de um inventário antigo: não pode aparecer como localizado agora
        $c->forceFill(['sit_inventario' => 'LOCALIZADO', 'id_inventario' => $inventario->id])->saveQuietly();

        $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $a->NumPatrimonio]);
        $this->api()->postJson('/mobile-api/conferencia/divergencias', ['bem_id' => $b->id, 'observacao' => 'Sem etiqueta.']);
        $a->forceFill(['id_inventario' => $inventario->id])->saveQuietly();
        $b->forceFill(['id_inventario' => $inventario->id])->saveQuietly();

        $this->actingAs($this->user, User::GUARD);
        Filament::setCurrentPanel('egap');

        Livewire::test(BensNoSetorTable::class)
            ->assertTableColumnFormattedStateSet('situacao_conferencia', 'Localizado', $a)
            ->assertTableColumnFormattedStateSet('situacao_conferencia', 'Divergente', $b)
            ->assertTableColumnFormattedStateSet('situacao_conferencia', 'Pendente', $c);

        $this->api()->postJson('/mobile-api/conferencia/nao-localizados', ['bem_ids' => [$c->id], 'justificativa' => 'Sumiu.']);

        Livewire::test(AtividadeDeCampoNaoLocalizadosTable::class, ['inventarioId' => $inventario->id])
            ->assertCanSeeTableRecords([$c->fresh()])
            ->assertCanNotSeeTableRecords([$a->fresh(), $b->fresh()]);

        $situacoes = app(ConferenciaBensService::class)->situacoesDoInventario($inventario);
        $this->assertSame(['localizado', 'divergente', 'nao_localizado'], [$situacoes[$a->id], $situacoes[$b->id], $situacoes[$c->id]]);
    }

    public function test_marcar_a_inventariar_inclui_bens_nunca_inventariados_e_preserva_divergentes(): void
    {
        $inventario = $this->inventarioAberto();
        [$a, $b] = $this->bens;
        $a->forceFill(['id_inventario' => null, 'sit_inventario' => null])->saveQuietly();
        $b->forceFill(['id_inventario' => $inventario->id, 'sit_inventario' => 'DIVERGENTE'])->saveQuietly();

        AbrangenciaInventario::marcarBensAInventariar($inventario);

        $this->assertSame('A INVENTARIAR', $a->fresh()->sit_inventario);
        $this->assertSame((int) $inventario->id, (int) $a->fresh()->id_inventario);
        $this->assertSame('DIVERGENTE', $b->fresh()->sit_inventario);
    }

    public function test_abrir_o_inventario_marca_os_bens_a_inventariar(): void
    {
        $inventario = $this->inventarioAberto();
        $this->bens[0]->forceFill(['id_inventario' => null, 'sit_inventario' => null])->saveQuietly();

        (new NotificarAberturaInventario($inventario->id))->handle();

        $this->assertSame('A INVENTARIAR', $this->bens[0]->fresh()->sit_inventario);
    }

    public function test_assinar_termos_e_da_comissao_e_so_do_inventario_atual(): void
    {
        $inventario = $this->inventarioAberto();
        [$a, $b] = $this->bens;
        $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $a->NumPatrimonio]);
        $this->api()->postJson('/mobile-api/conferencia/nao-localizados', ['bem_ids' => [$b->id], 'justificativa' => 'Sumiu.']);
        ItemInventario::query()->doInventario($inventario)->update(['id_complementosetor' => 1]);

        $this->noPainelCom([]);

        // lotado comum não assina
        Livewire::test(LevantamentoComissaoTable::class)->assertActionHidden(TestAction::make('assinar_termos')->table());

        $egap = UserEgap::currentAuthenticated();
        InventarioComissao::query()->create(['id_inventario' => $inventario->id, 'comissao' => 'Permanente', 'funcao' => 'Membro', 'nome' => $egap->getKey()]);

        Livewire::test(LevantamentoComissaoTable::class)
            ->assertActionVisible(TestAction::make('assinar_termos')->table())
            ->callAction(TestAction::make('assinar_termos')->table(), ['complementos' => [1]]);

        // só o localizado entra no termo
        $this->assertNotNull(ItemInventario::query()->where('id_bem', $a->id)->sole()->termo);
        $this->assertNull(ItemInventario::query()->where('id_bem', $b->id)->sole()->termo);
    }

    public function test_concluir_inventario_mostra_pendencias_e_encerra(): void
    {
        $inventario = $this->inventarioAberto();
        $this->api()->postJson('/mobile-api/conferencia/divergencias', ['bem_id' => $this->bens[0]->id, 'observacao' => 'Sem etiqueta.']);

        $this->assertSame(
            ['setores' => 1, 'finalizados' => 0, 'sem_finalizar' => 1, 'divergencias' => 1],
            InventarioResource::pendenciasDoInventario($inventario),
        );

        $this->noPainelCom(['egap_inventario']);

        Livewire::test(ListInventarios::class)
            ->assertActionVisible(TestAction::make('concluir_inventario')->table($inventario))
            ->callAction(TestAction::make('concluir_inventario')->table($inventario));

        $this->assertTrue($inventario->fresh()->estaFinalizado());
        $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $this->bens[1]->NumPatrimonio])->assertForbidden();
    }

    public function test_formulario_nao_conclui_direto(): void
    {
        $inventario = $this->inventarioAberto();
        $this->noPainelCom(['egap_inventario']);

        Livewire::test(EditInventario::class, ['record' => $inventario->getRouteKey()])
            ->fillForm(['situacao' => Inventario::SITUACAO_CONCLUIDO])
            ->call('save')
            ->assertHasFormErrors(['situacao']);

        $this->assertTrue($inventario->fresh()->estaAberto());
    }

    public function test_reabrir_setor_finalizado_avisa_os_lotados(): void
    {
        $inventario = $this->inventarioAberto();
        $this->conferirTudo();
        $this->api()->postJson('/mobile-api/conferencia/finalizar')->assertOk();
        $atividade = AtividadeInventario::query()->doInventario($inventario)->where('setor', $this->setor->id)->sole();

        $this->noPainelCom(['egap_patrimonio', 'egap_inventario']);

        Livewire::test(ListAtividadeInventarios::class)
            ->callAction(TestAction::make('reabrir_setor')->table($atividade), ['justificativa' => 'Faltou a sala 2.']);

        $this->assertSame('Em andamento', $atividade->fresh()->situacao);
        $this->assertCount(1, $this->notificacoes('inventario_reaberto'));
        $this->assertStringContainsString('Faltou a sala 2.', $this->notificacoes('inventario_reaberto')->first()->data['body']);

        $this->api()->getJson('/mobile-api/conferencia')->assertJsonPath('atividade.pode_editar', true);
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
            ->assertJsonPath('atividade.pode_editar', false)
            ->assertJsonPath('atividade.bloqueio.code', 'inventario_nao_aberto')
            ->assertJsonPath('atividade.pode_finalizar', false);

        // antes de abrir não grava nada
        $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $this->bens[0]->NumPatrimonio])->assertForbidden();

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

    public function test_inventario_novo_a_inventariar_nao_tira_o_lugar_do_em_execucao(): void
    {
        Bus::fake([NotificarAberturaInventario::class]);
        $prazo = CarbonImmutable::today()->addDays(5);
        $emExecucao = $this->novoInventario(Inventario::SITUACAO_EM_EXECUCAO, $prazo);
        $this->novoInventario(Inventario::SITUACAO_A_INVENTARIAR, null);

        $this->api()->getJson('/mobile-api/conferencia')
            ->assertOk()
            ->assertJsonPath('inventario.id', $emExecucao->id)
            ->assertJsonPath('atividade.pode_editar', true)
            ->assertJsonPath('atividade.bloqueio', null)
            ->assertJsonPath('atividade.prazo', $prazo->toDateString())
            ->assertJsonPath('atividade.prazo_vencido', false);
    }

    public function test_setor_fora_da_abrangencia_nao_confere(): void
    {
        Bus::fake([NotificarAberturaInventario::class]);
        $inventario = Inventario::query()->create([
            'num_inventario' => 98,
            'ano_inventario' => 2026,
            'situacao' => Inventario::SITUACAO_EM_EXECUCAO,
        ]);
        $outro = Setores::query()->setoresInventariaveis()->whereKeyNot($this->setor->id)
            ->whereNotIn('id', Setores::ancestraisIds((int) $this->setor->id))->firstOrFail();
        $inventario->unidadesInventariadas()->create(['unidades' => $outro->id, 'situacao' => Inventario::SITUACAO_A_INVENTARIAR]);

        $this->api()->getJson('/mobile-api/conferencia')
            ->assertOk()
            ->assertJsonPath('inventario.id', $inventario->id)
            ->assertJsonPath('atividade.pode_editar', false)
            ->assertJsonPath('atividade.bloqueio.code', 'fora_do_inventario');

        $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $this->bens[0]->NumPatrimonio])->assertForbidden();
        $this->api()->postJson('/mobile-api/conferencia/finalizar')->assertForbidden();
    }

    public function test_nao_localizado_reencontrado_vira_localizado(): void
    {
        Bus::fake([NotificarAberturaInventario::class]);
        $this->novoInventario(Inventario::SITUACAO_EM_EXECUCAO, null);
        $bem = $this->bens[0];

        $this->api()->postJson('/mobile-api/conferencia/nao-localizados', [
            'bem_ids' => [$bem->id],
            'justificativa' => 'Não estava na sala.',
        ])->assertSuccessful();

        $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $bem->NumPatrimonio])
            ->assertOk()
            ->assertJsonPath('status', 'relocalizado')
            ->assertJsonPath('bem.conferencia.status', 'localizado')
            ->assertJsonPath('resumo.localizados', 1)
            ->assertJsonPath('resumo.nao_localizados', 0);

        $item = ItemInventario::query()->where('id_bem', $bem->id)->sole();
        $this->assertSame('LOCALIZADO', $item->situacao);
        $this->assertStringContainsString('Não estava na sala.', (string) $item->observacao);
        $this->assertSame('LOCALIZADO', $bem->fresh()->sit_inventario);

        // terceira leitura só informa
        $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $bem->NumPatrimonio])
            ->assertOk()
            ->assertJsonPath('status', 'ja_conferido');
    }

    public function test_nao_localizados_ignora_bens_ja_conferidos(): void
    {
        Bus::fake([NotificarAberturaInventario::class]);
        $this->novoInventario(Inventario::SITUACAO_EM_EXECUCAO, null);
        [$a, $b] = $this->bens;

        $this->api()->postJson('/mobile-api/conferencia/leituras', ['codigo' => (string) $a->NumPatrimonio])
            ->assertJsonPath('status', 'localizado');

        $this->api()->postJson('/mobile-api/conferencia/nao-localizados', [
            'bem_ids' => [$a->id, $b->id],
            'justificativa' => 'Seleção em massa.',
        ])
            ->assertSuccessful()
            ->assertJsonPath('registrados', 1)
            ->assertJsonPath('ignorados', 1);

        $this->assertSame('LOCALIZADO', ItemInventario::query()->where('id_bem', $a->id)->sole()->situacao);
        $this->assertSame('NÃO LOCALIZADO', ItemInventario::query()->where('id_bem', $b->id)->sole()->situacao);

        // só bens já conferidos: nada a registrar
        $this->api()->postJson('/mobile-api/conferencia/nao-localizados', [
            'bem_ids' => [$a->id],
            'justificativa' => 'De novo.',
        ])->assertNotFound();
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
        // conferiu tudo: o lembrete diz que só falta finalizar (antes dizia "ainda há bens pendentes")
        $this->assertStringContainsString('Todos os bens já foram conferidos', $lembretes->first()->data['body']);
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
            ->assertNotified('Setor finalizado com sucesso!');

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
