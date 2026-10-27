<?php

namespace Tests\Feature;

use App\Helper\CpfHelper;
use App\Models\egap\Admin\InfoUser;
use App\Models\egap\Almoxarifado\FasePedido;
use App\Models\egap\Almoxarifado\ItemPedido;
use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\Cadastro\DescricaoDetalhada;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\User;
use App\Models\egap\UserEgap;
use App\Models\egap\UserLotacao;
use App\Services\Egap\Pedidos\NotificadorPedido;
use Database\Seeders\Spatie\EgapRolesPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\MobileApi\Concerns\UsaSessaoMobile;
use Tests\TestCase;

/**
 * Avisos ao solicitante a cada atualização do pedido (sino do EGAP e app), com usuários e pedido fictícios.
 *
 * Roda contra o banco real; tudo acontece dentro de uma transação desfeita no fim.
 */
class EgapNotificacoesPedidoTest extends TestCase
{
    use DatabaseTransactions;
    use UsaSessaoMobile;

    protected array $connectionsToTransact = ['emes', 'egap'];

    private Setores $setor;

    private User $solicitante;

    private UserEgap $solicitanteEgap;

    private User $atendente;

    /** @var array<int, DescricaoDetalhada> */
    private array $materiais;

    protected function setUp(): void
    {
        parent::setUp();

        NotificadorPedido::limpar();
        $this->seed(EgapRolesPermissionsSeeder::class);

        $this->setor = Setores::query()->setoresInventariaveis()->whereNotNull('codigodauo')->orderBy('id')->firstOrFail();
        $this->materiais = DescricaoDetalhada::query()
            ->whereNotNull('descricao_detalhada')
            ->where('descricao_detalhada', '<>', '')
            ->orderBy('id')
            ->limit(2)
            ->get()
            ->all();

        if (count($this->materiais) < 2) {
            $this->markTestSkipped('sem materiais de consumo neste banco');
        }

        [$this->solicitante, $this->solicitanteEgap] = $this->usuarioFicticio();
        [$this->atendente] = $this->usuarioFicticio();
    }

    protected function tearDown(): void
    {
        NotificadorPedido::limpar();

        parent::tearDown();
    }

    /** @return array{0: User, 1: UserEgap} */
    private function usuarioFicticio(): array
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

        $egap = UserEgap::query()->create(['name' => $user->name, 'username' => "teste.{$sufixo}", 'params' => '']);
        InfoUser::query()->create(['usuario_id' => $egap->id, 'cpf' => CpfHelper::format($cpf)]);

        $user->assignRole(User::ROLE_USER);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return [$user->fresh(), $egap];
    }

    private function novoPedido(?UserEgap $solicitante = null): Pedidos
    {
        $pedido = Pedidos::query()->create([
            'Solicitante' => ($solicitante ?? $this->solicitanteEgap)->id,
            'UnidadeJudiciaria' => $this->setor->codigopai ?: $this->setor->id,
            'Setor' => $this->setor->id,
            'idSituacao' => 6,
            'setor_responsavel' => 799,
            'Observacao' => 'Pedido fictício de teste',
        ]);

        foreach ($this->materiais as $i => $material) {
            ItemPedido::query()->create([
                'idPedido' => $pedido->id,
                'material' => $material->descricao_resumida,
                'DescricaoDetalhada' => $material->id,
                'QuantidadeMaterial' => 5 + $i,
                'QuantidadeMaterialAtendida' => 0,
                'situacao' => 6,
            ]);
        }

        // a criação (feita pelo próprio solicitante) não interessa aqui
        NotificadorPedido::limpar();

        return $pedido->fresh('itens');
    }

    /** @return Collection<int, DatabaseNotification> */
    private function avisosDe(User $user)
    {
        return $user->notifications()->orderByDesc('created_at')->get()
            ->filter(fn (DatabaseNotification $n): bool => ($n->data['viewData']['tipo'] ?? null) === NotificadorPedido::TIPO)
            ->values();
    }

    public function test_validacao_pela_equipe_gera_um_aviso_com_situacao_itens_responsavel_e_descricao(): void
    {
        $pedido = $this->novoPedido();
        $this->actingAs($this->atendente, User::GUARD);

        foreach ($pedido->itens as $item) {
            $item->update(['situacao' => 7, 'data_validacao' => now()]);
        }

        $pedido->update(['idSituacao' => 7]);
        FasePedido::query()->create(['idSituacao' => 7, 'Descricao' => 'Pedido validado pela Seção de Almoxarifado.', 'id_pedido' => $pedido->id]);

        $this->assertSame(1, NotificadorPedido::enviarPendentes(), 'várias mudanças na mesma ação viram um aviso só');

        $aviso = $this->avisosDe($this->solicitante)->sole();
        $corpo = $aviso->data['body'];

        $this->assertSame("Pedido nº {$pedido->id}: Validado", $aviso->data['title']);
        $this->assertStringContainsString('Em análise → <strong>Validado</strong>', $corpo);
        $this->assertStringContainsString('Atualizado em '.now()->format('d/m/Y').' às ', $corpo);
        $this->assertStringContainsString('por '.e($this->atendente->name), $corpo);
        $this->assertStringContainsString(e($this->materiais[0]->descricao_detalhada).' (5): Em análise → Validado', $corpo);
        $this->assertStringContainsString(e($this->materiais[1]->descricao_detalhada).' (6): Em análise → Validado', $corpo);
        $this->assertStringContainsString('Descrição: Pedido validado pela Seção de Almoxarifado.', $corpo);
        $this->assertSame("/pedidos/{$pedido->id}", $aviso->data['viewData']['rota_app']);
        $this->assertStringContainsString('almoxarifado/acompanhamento-de-pedidos', $aviso->data['actions'][0]['url']);

        $this->assertCount(0, $this->avisosDe($this->atendente), 'quem atendeu não recebe aviso');
    }

    public function test_numa_requisicao_o_aviso_e_gravado_sozinho_no_fim_dela(): void
    {
        $pedido = $this->novoPedido();

        Route::middleware('web')->post('/_teste/validar-pedido/{pedido}', function (Pedidos $pedido) {
            $pedido->itens->each(fn (ItemPedido $item) => $item->update(['situacao' => 7]));
            $pedido->update(['idSituacao' => 7]);

            return response()->noContent();
        });

        $this->actingAs($this->atendente, User::GUARD)
            ->post("/_teste/validar-pedido/{$pedido->id}")
            ->assertNoContent();

        $this->assertCount(1, $this->avisosDe($this->solicitante));
    }

    public function test_aviso_chega_no_app_em_texto_e_abre_o_pedido_com_historico(): void
    {
        $pedido = $this->novoPedido();
        $this->actingAs($this->atendente, User::GUARD);

        $item = $pedido->itens->first();
        $item->update(['situacao' => 9, 'QuantidadeMaterialAtendida' => 3]);
        FasePedido::query()->create([
            'idSituacao' => 9,
            'Descricao' => 'Item encaminhado para logística.',
            'id_pedido' => $pedido->id,
            'id_itempedido' => $item->id,
            'quantidade' => 3,
        ]);
        NotificadorPedido::enviarPendentes();

        [, $token] = $this->sessaoPara($this->solicitante);

        $notificacao = $this->withToken($token)->getJson('/mobile-api/notificacoes')
            ->assertOk()
            ->json('notificacoes.0');

        $this->assertSame(NotificadorPedido::TIPO, $notificacao['tipo']);
        $this->assertSame("Pedido nº {$pedido->id}: item em atendimento", $notificacao['titulo']);
        $this->assertSame("/pedidos/{$pedido->id}", $notificacao['rota_app']);
        $this->assertStringContainsString('Em análise → Em atendimento — atendidos 3 de 5', $notificacao['corpo']);
        $this->assertStringNotContainsString('<', $notificacao['corpo']);

        $detalhe = $this->withToken($token)->getJson("/mobile-api/pedidos/{$pedido->id}")
            ->assertOk()
            ->json('pedido');

        $this->assertSame($pedido->id, $detalhe['id']);
        $this->assertSame($this->materiais[0]->descricao_detalhada, $detalhe['itens'][0]['material']);
        $this->assertSame(3, $detalhe['itens'][0]['quantidade_atendida']);
        $this->assertSame('Item encaminhado para logística.', $detalhe['historico'][0]['descricao']);
        $this->assertSame('Em atendimento', $detalhe['historico'][0]['situacao']['descricao']);
        $this->assertSame($this->materiais[0]->descricao_detalhada, $detalhe['historico'][0]['material']);
    }

    public function test_pedido_de_outra_pessoa_nao_abre_no_app(): void
    {
        [, $outroEgap] = $this->usuarioFicticio();
        $pedido = $this->novoPedido($outroEgap);
        [, $token] = $this->sessaoPara($this->solicitante);

        $this->withToken($token)->getJson("/mobile-api/pedidos/{$pedido->id}")->assertNotFound();
    }

    public function test_cancelamento_leva_a_observacao_de_quem_atendeu(): void
    {
        $pedido = $this->novoPedido();
        $this->actingAs($this->atendente, User::GUARD);

        $pedido->itens->first()->update([
            'situacao' => 4,
            'ObservacaoItem' => 'Material sem estoque até o fim do mês.',
            'data_cancelado' => now(),
        ]);
        NotificadorPedido::enviarPendentes();

        $aviso = $this->avisosDe($this->solicitante)->sole();

        $this->assertSame("Pedido nº {$pedido->id}: item cancelado", $aviso->data['title']);
        $this->assertStringContainsString('Observação:<br>'.e($this->materiais[0]->descricao_detalhada).': Material sem estoque até o fim do mês.', $aviso->data['body']);
    }

    public function test_quem_altera_o_proprio_pedido_nao_e_avisado(): void
    {
        $pedido = $this->novoPedido();
        $this->actingAs($this->solicitante, User::GUARD);

        $pedido->itens->first()->update(['situacao' => 4, 'data_cancelado' => now()]);

        $this->assertSame(0, NotificadorPedido::enviarPendentes());
        $this->assertCount(0, $this->avisosDe($this->solicitante));
    }

    public function test_alteracao_desfeita_nao_gera_aviso(): void
    {
        $pedido = $this->novoPedido();
        $this->actingAs($this->atendente, User::GUARD);

        try {
            DB::connection('emes')->transaction(function () use ($pedido): void {
                $pedido->update(['idSituacao' => 5]);

                throw new RuntimeException('falha no meio da ação');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame(0, NotificadorPedido::enviarPendentes());
    }

    public function test_pedido_registrado_pela_equipe_em_nome_do_solicitante(): void
    {
        $this->actingAs($this->atendente, User::GUARD);

        $pedido = Pedidos::query()->create([
            'Solicitante' => $this->solicitanteEgap->id,
            'UnidadeJudiciaria' => $this->setor->codigopai ?: $this->setor->id,
            'Setor' => $this->setor->id,
            'idSituacao' => 6,
            'setor_responsavel' => 1239,
        ]);
        ItemPedido::query()->create([
            'idPedido' => $pedido->id,
            'DescricaoDetalhada' => $this->materiais[0]->id,
            'QuantidadeMaterial' => 1,
            'situacao' => 6,
        ]);

        NotificadorPedido::enviarPendentes();
        $aviso = $this->avisosDe($this->solicitante)->sole();

        $this->assertSame("Pedido nº {$pedido->id}: registrado em seu nome", $aviso->data['title']);
        $this->assertStringContainsString('Situação: <strong>Em análise</strong>', $aviso->data['body']);
        $this->assertStringContainsString('• '.e($this->materiais[0]->descricao_detalhada).' (1)', $aviso->data['body']);
        $this->assertStringContainsString('patrimonio/acompanhamento-de-pedidos', $aviso->data['actions'][0]['url']);
    }

    public function test_entrega_registrada_so_em_fase_vira_aviso_de_atendido(): void
    {
        $pedido = $this->novoPedido();

        // o que ArquivoDigital::validar() faz depois do update em massa das fases
        NotificadorPedido::faseRegistrada($pedido->id, 3, 'Termo nº 12/2026 validado: os bens foram entregues no setor.');
        NotificadorPedido::enviarPendentes();

        $aviso = $this->avisosDe($this->solicitante)->sole();

        $this->assertSame("Pedido nº {$pedido->id}: Atendido", $aviso->data['title']);
        $this->assertStringContainsString('Termo nº 12/2026 validado', $aviso->data['body']);
    }
}
