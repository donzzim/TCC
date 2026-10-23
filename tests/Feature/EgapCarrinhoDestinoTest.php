<?php

namespace Tests\Feature;

use App\Filament\Egap\Auth\EgapSession;
use App\Filament\Egap\Externo\Groups\Almoxarifado\Components\CarrinhoMateriaisConsumoForm;
use App\Filament\Egap\Externo\Groups\Patrimonio\Pages\BensNoSetor;
use App\Filament\Egap\Support\SetorSelecionado;
use App\Filament\Egap\Support\UsuarioSelecionado;
use App\Models\egap\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Exercita o destino do pedido do Ambiente Externo (Solicitante / Unidade
 * Judiciária / Setor) contra o banco real, em modo somente-leitura: nenhum
 * teste aqui grava pedido.
 */
class EgapCarrinhoDestinoTest extends TestCase
{
    public function test_it_resolves_setor_and_unidade_from_the_active_lotacao(): void
    {
        $this->autenticarComLotacao();

        $setor = EgapSession::setorAtual();
        $unidade = EgapSession::unidadeAtual();

        $this->assertNotNull($setor, 'usuário de teste precisa ter lotação');
        $this->assertNotNull($unidade);

        // setor e unidade têm de sair da MESMA linha de `setores`
        $linha = DB::connection('emes')->table('setores')->where('id', $setor)->first();
        $this->assertSame((int) $linha->codigodauo, $unidade);

        $rotulos = EgapSession::rotulosLotacaoAtual();
        $this->assertSame($linha->setor, $rotulos['setor']);
        $this->assertSame($linha->unidadeorganizacional, $rotulos['unidade']);
    }

    public function test_info_comes_from_users_and_user_perfil(): void
    {
        $user = $this->autenticarComLotacao();

        $this->assertSame([
            'nome' => $user->name,
            'cpf' => $user->cpf,
            'cargo' => DB::connection('emes')->table('user_perfil')->where('user_id', $user->id)->value('cargo'),
        ], EgapSession::info());
    }

    public function test_the_navbar_switcher_changes_setor_and_unidade_together(): void
    {
        $user = $this->autenticarComLotacao();

        $lotacoes = EgapSession::lotacoes();
        $this->assertGreaterThan(1, $lotacoes->count(), 'usuário de teste precisa ter várias lotações');

        foreach ([$lotacoes->first(), $lotacoes->last()] as $lotacao) {
            // é assim que o SetorSwitcher do topo grava
            session([EgapSession::SESSION_KEY_SETOR => $lotacao->id]);

            $this->assertSame((int) $lotacao->id, EgapSession::setorAtual());
            $this->assertSame((int) $lotacao->codigodauo, EgapSession::unidadeAtual());
        }
    }

    public function test_it_ignores_a_setor_the_user_is_not_lotado_in(): void
    {
        $this->autenticarComLotacao();

        $principal = EgapSession::setorAtual();

        // um setor real, mas de outra pessoa
        $alheio = DB::connection('emes')->table('setores')
            ->whereNotIn('id', EgapSession::lotacoes()->pluck('id'))
            ->value('id');

        session([EgapSession::SESSION_KEY_SETOR => $alheio]);

        $this->assertSame($principal, EgapSession::setorAtual(), 'setor alheio não pode virar lotação ativa');
        $this->assertSame($principal, session(EgapSession::SESSION_KEY_SETOR), 'o seletor do topo tem de voltar para a lotação válida');
        $this->assertSame($principal, session(EgapSession::SESSION_KEY . '.lotacao.setor'));
    }

    public function test_the_destination_never_comes_from_the_request(): void
    {
        $this->autenticarComLotacao();

        $metodo = new ReflectionMethod(CarrinhoMateriaisConsumoForm::class, 'destinoDoPedido');
        $metodo->setAccessible(true);

        // um request malicioso tentando escolher outro solicitante e outro setor
        $destino = $metodo->invoke(new CarrinhoMateriaisConsumoForm, [
            'Solicitante' => 999999,
            'UnidadeJudiciaria' => 888888,
            'Setor' => 777777,
            'ComplementoSetor' => 12,
        ]);

        $this->assertSame(EgapSession::resolverAtual(), $destino['Solicitante']);
        $this->assertNotSame(999999, $destino['Solicitante']);
        $this->assertSame(EgapSession::setorAtual(), $destino['Setor']);
        $this->assertSame(EgapSession::unidadeAtual(), $destino['UnidadeJudiciaria']);
        $this->assertSame(12, $destino['ComplementoSetor'], 'complemento continua vindo do form');
    }

    /**
     * O formulário não mostra nem deixa escolher o destino: ele vem da lotação
     * ativa no seletor do topo. Só o Complemento do Setor é preenchido.
     */
    public function test_the_cart_form_does_not_let_the_user_pick_the_destination(): void
    {
        $this->autenticarComLotacao();

        $componente = Livewire::test(CarrinhoMateriaisConsumoForm::class)
            ->assertOk()
            ->assertFormFieldExists('ComplementoSetor')
            ->assertFormFieldDoesNotExist('Solicitante')
            ->assertFormFieldDoesNotExist('UnidadeJudiciaria')
            ->assertFormFieldDoesNotExist('Setor')
            ->instance();

        $this->assertTrue($this->destinoVemDaLotacao($componente), 'destino vem da lotação');
    }

    public function test_it_falls_back_to_manual_selects_without_lotacao(): void
    {
        $semLotacao = User::query()
            ->whereDoesntHave('setores')
            ->first();

        if (! $semLotacao) {
            $this->markTestSkipped('nenhum usuário sem lotação no banco');
        }

        $this->actingAs($semLotacao, User::GUARD);

        $this->assertFalse(EgapSession::lotacaoUtilizavelComoDestino());
        $this->assertNull(EgapSession::setorAtual());

        $componente = Livewire::test(CarrinhoMateriaisConsumoForm::class)
            ->assertOk()
            ->instance();

        $this->assertFalse($this->destinoVemDaLotacao($componente), 'caiu nos selects manuais');
    }

    public function test_the_support_classes_now_resolve_from_the_lotacao(): void
    {
        $this->autenticarComLotacao();

        // sem escolha manual na sessão, tudo vem da lotação
        $this->assertSame(EgapSession::setorAtual(), SetorSelecionado::resolverAtual());
        $this->assertSame(EgapSession::unidadeAtual(), SetorSelecionado::resolverUnidadeAtual());
        $this->assertSame(EgapSession::resolverAtual(), UsuarioSelecionado::resolverAtual());
    }

    /**
     * Os modais de identificação saíram (traits `SelecionaSetorAtual` e
     * `SelecionaUsuarioAtual`). Nada mais escreve numa chave de sessão que
     * sobreponha a lotação — este teste existe para a substituição não voltar
     * pela porta dos fundos.
     */
    public function test_nothing_in_the_session_can_override_the_lotacao(): void
    {
        $this->autenticarComLotacao();

        session([
            'externo.patrimonio.setor_selecionado' => ['unidade' => 4242, 'setor' => 2424],
            'externo.agendamento.usuario_selecionado' => 1313,
        ]);

        $this->assertSame(EgapSession::setorAtual(), SetorSelecionado::resolverAtual());
        $this->assertSame(EgapSession::unidadeAtual(), SetorSelecionado::resolverUnidadeAtual());
        $this->assertSame(EgapSession::resolverAtual(), UsuarioSelecionado::resolverAtual());

        $this->assertNotSame(2424, SetorSelecionado::resolverAtual());
        $this->assertNotSame(1313, UsuarioSelecionado::resolverAtual());
    }

    public function test_externo_pages_open_straight_into_the_data(): void
    {
        // a página exige papel do Ambiente Externo, não só lotação
        $user = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['egap_admin', 'egap_super_admin']))
            ->whereHas('setores')
            ->get()
            ->first(fn (User $u) => filled(EgapSession::formatosDeBusca($u->cpf)));

        if (! $user) {
            $this->markTestSkipped('nenhum usuário com acesso ao Externo e lotação neste banco');
        }

        $this->actingAs($user, User::GUARD);
        Filament::setCurrentPanel('egap');

        $this->assertNotNull(EgapSession::setorAtual(), 'lotação precisa resolver para o modal não abrir');

        Livewire::test(BensNoSetor::class)
            ->assertOk()
            ->assertNotDispatched('open-modal');
    }

    /**
     * O caminho HTTP completo da página que o usuário abre, com middleware,
     * painel e renderização de verdade.
     */
    public function test_the_requisicao_page_answers_over_http(): void
    {
        $user = $this->usuarioComAcessoExterno();

        $rotulos = EgapSession::rotulosLotacaoAtual();

        $this->actingAs($user, User::GUARD)
            ->get('/egap/externo/almoxarifado/requisicao-de-materiais-de-consumo')
            ->assertOk()
            // o destino aparece no topo da página (usuário e seletor de lotação)
            ->assertSee($user->name)
            ->assertSee($rotulos['setor']);
    }

    private function usuarioComAcessoExterno(): User
    {
        $user = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['egap_admin', 'egap_super_admin']))
            ->whereHas('setores')
            ->get()
            ->first(fn (User $u) => filled(EgapSession::formatosDeBusca($u->cpf)));

        if (! $user) {
            $this->markTestSkipped('nenhum usuário com acesso ao Externo e lotação neste banco');
        }

        $this->actingAs($user, User::GUARD);
        Filament::setCurrentPanel('egap');

        return $user;
    }

    private function destinoVemDaLotacao(object $componente): bool
    {
        $metodo = new ReflectionMethod($componente, 'destinoVemDaLotacao');
        $metodo->setAccessible(true);

        return $metodo->invoke($componente);
    }

    /**
     * Um usuário real com mais de uma lotação, CPF válido e lotação principal
     * que preenche o destino sozinha (unidade inventariável). Buscado em vez de
     * fixo por id: os ids mudam quando as migrações são rodadas de novo.
     */
    private function autenticarComLotacao(): User
    {
        $candidatos = User::query()
            ->has('setores', '>', 1)
            ->whereNotNull('cpf')
            ->orderBy('id')
            ->limit(100)
            ->get();

        foreach ($candidatos as $user) {
            if (blank(EgapSession::formatosDeBusca($user->cpf))) {
                continue;
            }

            $this->actingAs($user, User::GUARD);
            session()->forget([EgapSession::SESSION_KEY, EgapSession::SESSION_KEY_SETOR]);

            if (EgapSession::lotacaoUtilizavelComoDestino()) {
                session()->forget([EgapSession::SESSION_KEY, EgapSession::SESSION_KEY_SETOR]);

                return $user;
            }
        }

        $this->markTestSkipped('nenhum usuário com várias lotações e destino automático neste banco');
    }
}
